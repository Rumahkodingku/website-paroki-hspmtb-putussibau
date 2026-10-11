<?php

use App\Enums\DayOfWeek;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\ScheduleNotice;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| The public /jadwal-misa page
|--------------------------------------------------------------------------
|
| This file is the backend half of PRD 7.3, and it is where the module's rules
| are checked from the outside: a visitor must see the next mass, must not see a
| mass at a location the parish switched off, and must not be handed a payload
| carrying an internal field.
|
| Three properties are asserted that no single business rule owns:
|
|   - the props are plain arrays, not {"data": [...]}. A resource collection's
|     envelope exists to carry pagination; this page has none, and a wrapper
|     here would also make an empty list read as one element to a count.
|
|   - nothing internal leaks. Part C rule 2 says send only what the page renders,
|     and is_active / notes / can are not rendered here.
|
|   - a malformed filter is ignored rather than rejected. /jadwal-misa?hari=99
|     should show the full schedule, not a 422 to somebody who followed a
|     mangled link (roadmap 5.5).
*/

beforeEach(function () {
    $this->location = Location::factory()->create(['name' => 'Gereja Paroki']);
    $this->other = Location::factory()->create(['name' => 'Stasi Tanah Empang']);
});

/*
|--------------------------------------------------------------------------
| Reachability
|--------------------------------------------------------------------------
*/

it('renders for a guest', function () {
    $this->get('/jadwal-misa')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/jadwal-misa'));
});

/*
|--------------------------------------------------------------------------
| The next mass
|--------------------------------------------------------------------------
*/

it('sends the next mass with a time in Asia/Pontianak', function () {
    MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create();

    Carbon::setTestNow(Carbon::parse('2026-10-04 05:00', config('app.display_timezone'))->utc());

    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextMass.kind', 'rutin')
            ->where('nextMass.name', MassSchedule::query()->value('name'))
            // The resolver converted once, here. Sending UTC instead would make
            // React convert a second time, which is where "misa berikutnya"
            // quietly drifts by a day.
            ->where('nextMass.starts_at', '2026-10-04T07:30:00+07:00')
            ->where('nextMass.location.name', 'Gereja Paroki')
        );

    Carbon::setTestNow();
});

it('sends null when nothing is scheduled', function () {
    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page->where('nextMass', null));
});

/*
|--------------------------------------------------------------------------
| Lists
|--------------------------------------------------------------------------
*/

it('sends recurring masses as a plain array, ordered day then hour', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '10:00')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    MassSchedule::factory()->for($this->other)->onDay(DayOfWeek::Senin, '06:00')->create();

    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularMasses', 3)
            ->where('regularMasses.0.start_time', '07:30')
            ->where('regularMasses.1.start_time', '10:00')
            ->where('regularMasses.2.day_of_week', 1)
        );
});

it('sends no internal fields to a visitor', function () {
    MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')
        ->create(['notes' => 'Catatan internal.']);

    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page
            // Part C rule 2: everything in the page source is public.
            ->missing('regularMasses.0.is_active')
            ->missing('regularMasses.0.can')
            ->missing('regularMasses.0.location.notes')
            ->missing('regularMasses.0.location.maps_url')
            // The location list is shaped by the public resource, not the admin
            // one. Sending LocationResource here published the slug, the raw
            // coordinates, the internal notes and is_active on a public page.
            ->missing('locations.0.slug')
            ->missing('locations.0.latitude')
            ->missing('locations.0.longitude')
            ->missing('locations.0.notes')
            ->missing('locations.0.maps_url')
            ->missing('locations.0.is_active')
            ->missing('locations.0.created_at')
            ->missing('locations.0.can')
            // A recurring mass's own notes ARE shown (roadmap 7.2 asks for
            // "catatan relevan"), so this is present rather than missing.
            ->where('regularMasses.0.notes', 'Catatan internal.')
        );
});

it('omits an inactive schedule and an inactive location\'s schedules', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    MassSchedule::factory()->for($this->location)->inactive()->onDay(DayOfWeek::Senin, '06:00')->create();

    $hidden = Location::factory()->inactive()->create();
    MassSchedule::factory()->for($hidden)->onDay(DayOfWeek::Selasa, '06:00')->create();

    // Two active locations from beforeEach; the inactive one adds nothing.
    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularMasses', 1)
            ->has('locations', 2)
        );
});

it('lists only active locations as filter options', function () {
    Location::factory()->inactive()->create();

    // beforeEach already made two; a third, inactive, must not appear.
    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page->has('locations', 2));
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

it('filters by location', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    MassSchedule::factory()->for($this->other)->onDay(DayOfWeek::Minggu, '10:00')->create();

    $this->get('/jadwal-misa?lokasi='.$this->other->getKey())
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularMasses', 1)
            ->where('regularMasses.0.location.name', 'Stasi Tanah Empang')
            ->where('filters.location_id', $this->other->getKey())
        );
});

it('filters by day', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Senin, '06:00')->create();

    $this->get('/jadwal-misa?hari=1')
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularMasses', 1)
            ->where('regularMasses.0.day_of_week', 1)
        );
});

it('applies location and day together', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Senin, '06:00')->create();
    MassSchedule::factory()->for($this->other)->onDay(DayOfWeek::Senin, '06:00')->create();

    $this->get('/jadwal-misa?lokasi='.$this->other->getKey().'&hari=1')
        ->assertInertia(fn (Assert $page) => $page->has('regularMasses', 1));
});

it('echoes the filter it actually applied', function () {
    $this->get('/jadwal-misa?hari=3')
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.day', 3)
            ->where('filters.location_id', null)
        );
});

/*
|--------------------------------------------------------------------------
| A malformed filter is ignored, not rejected
|--------------------------------------------------------------------------
*/

it('ignores a day outside 0 to 6 instead of returning 422', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->get('/jadwal-misa?hari=99')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularMasses', 1)
            ->where('filters.day', null)
        );
});

it('ignores a negative day', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->get('/jadwal-misa?hari=-1')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('regularMasses', 1));
});

it('ignores a location id that does not exist', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->get('/jadwal-misa?lokasi=999999')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularMasses', 1)
            ->where('filters.location_id', null)
        );
});

it('ignores a deactivated location id rather than showing an empty page', function () {
    $hidden = Location::factory()->inactive()->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    // A link written down while the location was active must still explain
    // itself rather than render a page with nothing on it and no reason.
    $this->get('/jadwal-misa?lokasi='.$hidden->getKey())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularMasses', 1)
            ->where('filters.location_id', null)
        );
});

it('ignores a non-numeric location parameter', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->get('/jadwal-misa?lokasi=gereja-paroki')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('regularMasses', 1));
});

/*
|--------------------------------------------------------------------------
| AC-MASS-3 — an admin change shows on the next request
|--------------------------------------------------------------------------
*/

it('reflects an admin change on the following request', function () {
    $schedule = MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create(['name' => 'Nama lama']);

    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page
            ->where('regularMasses.0.name', 'Nama lama')
        );

    // As the admin would, through the real endpoint.
    $this->actingAs(superAdmin())
        ->patch(route('mass-schedules.update', $schedule), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Nama baru',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    // Nothing is cached (D-35), so this request is all it takes. A cache would
    // make AC-MASS-3 fail with no visible error anywhere.
    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page
            ->where('regularMasses.0.name', 'Nama baru')
        );
});

it('reflects a new mass on the following request', function () {
    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page->has('regularMasses', 0));

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Misa baru',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page->has('regularMasses', 1));
});

/*
|--------------------------------------------------------------------------
| The map link, built server-side (D-8)
|--------------------------------------------------------------------------
*/

it('builds a map link from coordinates when there is no stored url', function () {
    // One location only, so the props hold exactly one entry and the assertion
    // does not depend on the alphabetical order the list is returned in.
    Location::query()->delete();
    $located = Location::factory()->withCoordinates(-0.0293, 111.7686)->create();

    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page
            ->has('locations', 1)
            ->where('locations.0.mapsUrl', 'https://www.google.com/maps/search/?api=1&query=-0.0293000,111.7686000')
        );

    expect($located->fresh()->mapsUrl())->toStartWith('https://www.google.com/maps/search/');
});

it('sends null when a location has nothing to point at', function () {
    Location::query()->delete();
    Location::factory()->withoutMaps()->create();

    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page->has('locations.0.mapsUrl', null));
});

/*
|--------------------------------------------------------------------------
| Notices
|--------------------------------------------------------------------------
*/

it('sends a general notice in its window', function () {
    ScheduleNotice::factory()->general()->create(['title' => 'Penting']);

    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page
            ->has('notices', 1)
            ->where('notices.0.title', 'Penting')
            ->where('notices.0.is_general', true)
        );
});

it('sends nothing when there is no notice to show', function () {
    $this->get('/jadwal-misa')
        ->assertInertia(fn (Assert $page) => $page->has('notices', 0));
});
