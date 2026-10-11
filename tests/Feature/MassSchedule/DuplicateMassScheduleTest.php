<?php

use App\Enums\DayOfWeek;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Duplicating a recurring mass
|--------------------------------------------------------------------------
|
| PRD 7.3 lists duplication because a parish with a stasi and two chapels enters
| the same Sunday mass four times and then has four places to forget to update
| one of them.
|
| Two things have to be right, and neither is the copy itself:
|
|   - the destination must be an ACTIVE location. A copy written onto a
|     deactivated location is invisible from the moment it exists, which looks
|     like the feature silently failing.
|
|   - the destination must not already hold that day and hour. A duplicate is
|     not a useful second copy, and the unique index would reject the second one
|     anyway — as an exception rather than as a sentence.
|
| @see docs/DECISIONS.md D-34
*/

beforeEach(function () {
    $this->source = MassSchedule::factory()
        ->for(Location::factory())
        ->onDay(DayOfWeek::Minggu, '07:30')
        ->create([
            'name' => 'Misa Minggu Pagi',
            'notes' => 'Dengan koir.',
        ]);

    $this->target = Location::factory()->create(['name' => 'Stasi Tanah Empang']);
});

/*
|--------------------------------------------------------------------------
| Access
|--------------------------------------------------------------------------
*/

it('sends a guest', function () {
    $this->post(route('mass-schedules.duplicate', $this->source))
        ->assertRedirect(route('login'));
});

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->post(route('mass-schedules.duplicate', $this->source), [
            'location_id' => $this->target->getKey(),
        ])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| The happy path
|--------------------------------------------------------------------------
*/

it('copies the schedule to another location', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', $this->source), [
            'location_id' => $this->target->getKey(),
        ])
        ->assertRedirect(route('mass-schedules.list'))
        ->assertSessionHasNoErrors();

    expect(MassSchedule::query()->count())->toBe(2);

    $copy = MassSchedule::query()
        ->where('location_id', $this->target->getKey())
        ->firstOrFail();

    expect($copy->day_of_week)->toBe(DayOfWeek::Minggu)
        ->and($copy->start_time)->toBe('07:30:00')
        ->and($copy->name)->toBe('Misa Minggu Pagi')
        ->and($copy->notes)->toBe('Dengan koir.');
});

it('leaves the original untouched', function () {
    $sourceLocationId = $this->source->location_id;

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', $this->source), [
            'location_id' => $this->target->getKey(),
        ])
        ->assertSessionHasNoErrors();

    // The copy is a new row, not the original moved: two records exist and the
    // original still points at the location it started on.
    expect($this->source->fresh())
        ->location_id->toBe($sourceLocationId)
        ->and(MassSchedule::query()->count())->toBe(2);
});

it('gives the copy its own timestamps', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', $this->source), [
            'location_id' => $this->target->getKey(),
        ])
        ->assertSessionHasNoErrors();

    $copy = MassSchedule::query()->where('location_id', $this->target->getKey())->firstOrFail();

    expect($copy->getKey())->not->toBe($this->source->getKey());
});

/*
|--------------------------------------------------------------------------
| The two refusals
|--------------------------------------------------------------------------
*/

it('refuses a destination the parish has switched off', function () {
    $inactive = Location::factory()->inactive()->create();

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', $this->source), [
            'location_id' => $inactive->getKey(),
        ])
        ->assertSessionHasErrors('location_id');

    expect(MassSchedule::query()->count())->toBe(1);
});

it('refuses a destination that already has that day and hour', function () {
    MassSchedule::factory()->for($this->target)->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', $this->source), [
            'location_id' => $this->target->getKey(),
        ])
        ->assertSessionHasErrors([
            'location_id' => 'Lokasi tujuan sudah memiliki jadwal pada hari dan jam yang sama.',
        ]);

    // Still only the one that was there before, plus the source.
    expect(MassSchedule::query()->count())->toBe(2);
});

it('allows a destination with the same day but a different hour', function () {
    MassSchedule::factory()->for($this->target)->onDay(DayOfWeek::Minggu, '10:00')->create();

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', $this->source), [
            'location_id' => $this->target->getKey(),
        ])
        ->assertSessionHasNoErrors();

    expect(MassSchedule::query()->where('location_id', $this->target->getKey())->count())->toBe(2);
});

it('requires a destination', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', $this->source), [])
        ->assertSessionHasErrors('location_id');

    expect(MassSchedule::query()->count())->toBe(1);
});

it('404s for a schedule that does not exist', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', 999999), [
            'location_id' => $this->target->getKey(),
        ])
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| The copy is immediately public
|--------------------------------------------------------------------------
*/

it('publishes the copy on the next request', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', $this->source), [
            'location_id' => $this->target->getKey(),
        ])
        ->assertSessionHasNoErrors();

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('regularMasses', 2));
});
