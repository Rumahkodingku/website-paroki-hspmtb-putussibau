<?php

use App\Enums\DayOfWeek;
use App\Enums\LocationType;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Recurring masses — /admin/jadwal-misa
|--------------------------------------------------------------------------
|
| The three rules this file is really about:
|
|   MASS-03  a schedule must point at an *active* location. Deactivating a
|            location hides its schedules from the public page, so accepting one
|            against a deactivated location writes a record nobody can see.
|
|   MASS-06  (location, day, hour) is unique — as a rule, so an admin gets a
|            sentence, and as an index, so two simultaneous saves cannot both
|            win. Both layers are exercised below.
|
|   D-02     start_time is a wall clock. A test that only checked the hour
|            displayed correctly would pass with the column cast to a datetime
|            and the offset applied; one that checks the stored bytes would not.
|
| @see docs/DECISIONS.md D-34, D-36
*/

beforeEach(function () {
    $this->location = Location::factory()->create(['name' => 'Gereja Paroki']);
});

/*
|--------------------------------------------------------------------------
| Access
|--------------------------------------------------------------------------
*/

it('sends a guest from the list and from the create form', function (string $name) {
    $this->get(route($name))->assertRedirect(route('login'));
})->with([
    'index' => fn () => 'mass-schedules.index',
    'create' => fn () => 'mass-schedules.create',
]);

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('mass-schedules.index'))
        ->assertForbidden();
});

it('refuses a guest from every mutating endpoint', function (string $method, string $name) {
    $this->{$method}(route($name, MassSchedule::factory()->create()))->assertRedirect(route('login'));
})->with([
    'store' => fn () => ['post', 'mass-schedules.store'],
    'update' => fn () => ['patch', 'mass-schedules.update'],
    'duplicate' => fn () => ['post', 'mass-schedules.duplicate'],
]);

/*
|--------------------------------------------------------------------------
| Listing
|--------------------------------------------------------------------------
*/

it('lists schedules grouped by day with Sunday first', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Sabtu, '17:00')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/jadwal-misa')
            ->has('massSchedules.data', 2)
            // The order is a contract with the public page, which groups the same
            // way; Saturday's 17.00 mass must not come before Sunday's.
            ->where('massSchedules.data.0.day_of_week', 0)
            ->where('massSchedules.data.1.day_of_week', 6)
            ->where('massSchedules.data.0.start_time', '07:30')
            ->has('massSchedules.data.0.location')
        );
});

it('counts the module statistics from the database', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    MassSchedule::factory()->for($this->location)->inactive()->onDay(DayOfWeek::Senin, '06:00')->create();

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.index'))
        ->assertInertia(fn (Assert $page) => $page
            // One active, one not — the number is a count, not the row count.
            ->where('stats.active_mass_schedules', 1)
            ->where('stats.active_locations', 1)
            ->where('stats.upcoming_special_masses', 0)
            ->where('stats.visible_notices', 0)
        );
});

it('searches by mass name and by location name', function () {
    $other = Location::factory()->create(['name' => 'Stasi Tanah Empang']);

    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')
        ->create(['name' => 'Misa Paroki Pagi']);
    MassSchedule::factory()->for($other)->onDay(DayOfWeek::Minggu, '10:00')
        ->create(['name' => 'Misa Stasi Pagi']);

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.index', ['search' => 'Empang']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('massSchedules.data', 1)
            ->where('massSchedules.data.0.name', 'Misa Stasi Pagi')
        );
});

/*
|--------------------------------------------------------------------------
| Creating
|--------------------------------------------------------------------------
*/

it('creates a recurring mass', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Misa Minggu Pagi',
            'language' => '',
            'notes' => '',
            'is_active' => true,
        ])
        ->assertRedirect(route('mass-schedules.index'))
        ->assertSessionHasNoErrors();

    expect(MassSchedule::query()->count())->toBe(1);
});

/**
 * D-02. The column must hold the wall clock it was given, not a datetime with
 * the UTC offset already applied.
 */
it('stores start_time as the wall clock it was given', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Misa Pagi',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    $stored = MassSchedule::query()->firstOrFail();

    expect($stored->start_time)->toBe('07:30:00')
        ->and($stored->displayTime())->toBe('07.30 WIB');
});

it('normalises a single digit hour', function () {
    // A hand-written request or a spreadsheet import sends "7:30".
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 0,
            'start_time' => '7:30',
            'name' => 'Misa Pagi',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect(MassSchedule::query()->value('start_time'))->toBe('07:30:00');
});

it('rejects a time that is not a clock', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 0,
            // A datetime is not a wall clock. Accepting it would store a date
            // that means nothing for a weekly recurrence.
            'start_time' => '2026-10-04T07:30',
            'name' => 'Misa Pagi',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('start_time');
});

it('rejects a day outside 0 to 6', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 9,
            'start_time' => '07:30',
            'name' => 'Misa Pagi',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('day_of_week');
});

it('rejects a missing name or location', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'day_of_week' => 0,
            'start_time' => '07:30',
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['name', 'location_id']);

    expect(MassSchedule::query()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| MASS-03 — the location must be active
|--------------------------------------------------------------------------
*/

it('refuses a schedule pointing at an inactive location', function () {
    $inactive = Location::factory()->inactive()->create();

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $inactive->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Misa Pagi',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('location_id');

    expect(MassSchedule::query()->count())->toBe(0);
});

it('refuses moving a schedule onto an inactive location', function () {
    $inactive = Location::factory()->inactive()->create();
    $schedule = MassSchedule::factory()->for($this->location)->create();

    $this->actingAs(superAdmin())
        ->patch(route('mass-schedules.update', $schedule), [
            'location_id' => $inactive->getKey(),
            'day_of_week' => $schedule->day_of_week->value,
            'start_time' => substr($schedule->start_time, 0, 5),
            'name' => $schedule->name,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('location_id');

    expect($schedule->fresh()->location_id)->toBe($this->location->getKey());
});

/*
|--------------------------------------------------------------------------
| MASS-06 — no duplicates
|--------------------------------------------------------------------------
*/

it('refuses a duplicate of the same location, day and hour', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Misa yang sama',
            'is_active' => true,
        ])
        // AC-MASS-7: a message an admin can act on, not "validation failed".
        ->assertSessionHasErrors([
            'location_id' => 'Sudah ada jadwal pada lokasi, hari, dan jam tersebut.',
        ]);

    expect(MassSchedule::query()->count())->toBe(1);
});

it('allows the same day and hour at a different location', function () {
    $other = Location::factory()->create();

    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $other->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Misa di lokasi lain',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect(MassSchedule::query()->count())->toBe(2);
});

it('treats an inactive schedule as still occupying its slot', function () {
    // MASS-04: the record stays, and the unique index covers it. A switched-off
    // mass therefore cannot be silently replaced — it has to be reactivated or
    // edited, which is what an admin would expect to have to do.
    MassSchedule::factory()->for($this->location)->inactive()->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Misa pengganti',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('location_id');
});

it('lets a schedule keep its own slot when updated', function () {
    $schedule = MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->actingAs(superAdmin())
        ->patch(route('mass-schedules.update', $schedule), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Misa Minggu Pagi (baru)',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($schedule->fresh()->name)->toBe('Misa Minggu Pagi (baru)');
});

/*
|--------------------------------------------------------------------------
| MASS-04 — deactivation, not deletion
|--------------------------------------------------------------------------
*/

it('deactivates a schedule through the ordinary update, keeping the row', function () {
    $schedule = MassSchedule::factory()->for($this->location)->create();

    $this->actingAs(superAdmin())
        ->patch(route('mass-schedules.update', $schedule), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => $schedule->day_of_week->value,
            'start_time' => substr($schedule->start_time, 0, 5),
            'name' => $schedule->name,
            'is_active' => false,
        ])
        ->assertRedirect(route('mass-schedules.index'));

    expect($schedule->fresh())->is_active->toBeFalse();

    expect(MassSchedule::query()->count())->toBe(1);
});

it('stops publishing a deactivated schedule on the next request', function () {
    $schedule = MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('regularMasses', 1));

    $schedule->update(['is_active' => false]);

    // Nothing is cached (D-35), so the next request is all it takes. This is
    // AC-MASS-3 for the module's own data.
    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('regularMasses', 0));
});

/*
|--------------------------------------------------------------------------
| Editing
|--------------------------------------------------------------------------
*/

it('renders the edit form with the schedule and the day labels', function () {
    $schedule = MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Sabtu, '17:00')->create();

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.edit', $schedule))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/jadwal-misa/misa-rutin/ubah')
            ->where('massSchedule.name', $schedule->name)
            ->where('massSchedule.start_time', '17:00')
            // The labels come from the language file rather than a second copy
            // in TypeScript, so the day names cannot drift from the enum.
            ->where('days.0.label', 'Minggu')
            ->where('days.6.label', 'Sabtu')
            // Counted, and the wrapper explicitly absent. A resource
            // collection Inertia wraps as {data: [...]} is an array of exactly
            // one key, so has('locations', 1) passes just as happily on the
            // broken shape as on the right one - and this page then renders
            // nothing at all in a browser. See D-39.
            ->where('locations.0.id', $this->location->id)
            ->missing('locations.data')
        );
});

it('404s for a schedule that does not exist', function () {
    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.edit', 999999))
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Location type keeps its enum
|--------------------------------------------------------------------------
*/

it('reads back a location type as its enum value', function () {
    $location = Location::factory()->create(['type' => LocationType::Stasi]);

    expect($location->fresh()->type)->toBe(LocationType::Stasi);
});
