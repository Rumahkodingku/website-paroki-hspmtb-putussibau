<?php

use App\Enums\DayOfWeek;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\ScheduleNotice;
use App\Models\SpecialMass;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Sorting, and the summary/list split
|--------------------------------------------------------------------------
|
| Two changes landed together in D-41, so they are tested together: the
| recurring-mass list moved off the summary page onto its own URL, and every
| list in the module gained a sortable header.
|
| Sorting is server-side because every list is server-paginated. Sorting the
| fifteen rows the browser holds would order fifteen rows and leave the rest
| where they were, which is the kind of small lie a table should not tell.
|
| The interesting cases are the three that fail silently rather than loudly:
|
|   default order   a sort that was not asked for must leave the list exactly
|                   as it was. Several earlier tests assert those orders, and
|                   they are the proof this feature did not quietly rearrange
|                   every page in the module.
|   unknown key     ignored, not rejected. A mangled link shows a list; it does
|                   not 422. The same reasoning as ?status= on the public page.
|   tie-breaker     without a total order, paginating over it drops rows: row
|                   16 of page 2 can also be row 1 of page 1.
|
| One more test here is about a query builder rather than a requirement:
| `select()` after `withCount()` silently drops the counts. See the comment in
| MassScheduleController::misaRutin().
|
| @see docs/DECISIONS.md D-41
| @see app/Concerns/SortsAdminList.php
*/

beforeEach(function () {
    $this->location = Location::factory()->create(['name' => 'Gereja Paroki']);
});

/*
|--------------------------------------------------------------------------
| The split
|--------------------------------------------------------------------------
*/

it('sends the summary URL to the list, which is where the module lives now', function () {
    MassSchedule::factory()->for($this->location)->create();

    // The summary page is gone, but the URL is not: it is the one PRD 5.2 names
    // and the one every breadcrumb in the module points at, so removing the route
    // would turn those breadcrumbs into 404s.
    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.index'))
        ->assertRedirect(route('mass-schedules.list'));

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/jadwal-misa/misa-rutin/index')
            ->has('massSchedules.data', 1)
            ->missing('stats')
        );
});

it('lands an admin on the list after saving, not on the summary', function () {
    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->id,
            'day_of_week' => DayOfWeek::Minggu->value,
            'start_time' => '07:30',
            'name' => 'Misa Baru',
            'is_active' => true,
        ])
        ->assertRedirect(route('mass-schedules.list'));
});

it('does not resolve /admin/jadwal-misa/misa-rutin as a schedule id', function () {
    // 'misa-rutin' is declared before {mass_schedule}, so the literal wins.
    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.edit', 'misa-rutin'))
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Default order survives
|--------------------------------------------------------------------------
*/

it('leaves the location list in name order when no sort is asked for', function () {
    Location::factory()->create(['name' => 'Zebra']);
    Location::factory()->create(['name' => 'Alpha']);

    $this->actingAs(superAdmin())
        ->get(route('locations.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations.data.0.name', 'Alpha')
            ->where('locations.data.1.name', 'Gereja Paroki')
            ->where('locations.data.2.name', 'Zebra')
        );
});

it('leaves the recurring-mass list grouped by day when no sort is asked for', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Sabtu, '17:00')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '09:00')->create();

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('massSchedules.data.0.day_of_week', DayOfWeek::Minggu->value)
            ->where('massSchedules.data.1.day_of_week', DayOfWeek::Minggu->value)
            ->where('massSchedules.data.2.day_of_week', DayOfWeek::Sabtu->value)
        );
});

it('leaves the notice list newest-window-first when no sort is asked for', function () {
    ScheduleNotice::factory()->create([
        'title' => 'Yang lama',
        'starts_at' => now()->subWeek(),
    ]);
    ScheduleNotice::factory()->create([
        'title' => 'Yang baru',
        'starts_at' => now()->addWeek(),
    ]);

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.index'))
        ->assertInertia(fn (Assert $page) => $page
            // orderByDesc(starts_at) is the module's default; D-41 did not
            // quietly replace it with an ascending one.
            ->where('notices.data.0.title', 'Yang baru')
            ->where('notices.data.1.title', 'Yang lama')
        );
});

/*
|--------------------------------------------------------------------------
| Sorting works
|--------------------------------------------------------------------------
*/

it('sorts the location list by name in either direction', function () {
    Location::factory()->create(['name' => 'Zebra']);
    Location::factory()->create(['name' => 'Alpha']);

    $this->actingAs(superAdmin())
        ->get(route('locations.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations.data.0.name', 'Alpha')
            ->where('locations.data.2.name', 'Zebra')
        );

    $this->actingAs(superAdmin())
        ->get(route('locations.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations.data.0.name', 'Zebra')
            ->where('locations.data.2.name', 'Alpha')
        );
});

it('sorts the recurring-mass list by name', function () {
    MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Sabtu, '17:00')->create(['name' => 'Zulu']);
    MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create(['name' => 'Alfa']);

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list', ['sort' => 'name', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('massSchedules.data.0.name', 'Alfa')
            ->where('massSchedules.data.1.name', 'Zulu')
        );
});

it('sorts by location name through the join', function () {
    $stasi = Location::factory()->create(['name' => 'Stasi Zulu']);
    $kapel = Location::factory()->create(['name' => 'Kapel Alfa']);

    MassSchedule::factory()->for($kapel)->onDay(DayOfWeek::Minggu, '06:00')
        ->create(['name' => 'Misa di kapel']);
    MassSchedule::factory()->for($stasi)->onDay(DayOfWeek::Senin, '06:00')
        ->create(['name' => 'Misa di stasi']);
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Selasa, '06:00')
        ->create(['name' => 'Misa di paroki']);

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list', ['sort' => 'location', 'direction' => 'asc']))
        // Ascending by location name: "Gereja Paroki" < "Kapel Alfa" <
        // "Stasi Zulu". The order of the names, not the order they were created
        // in — that is the whole claim being tested.
        ->assertInertia(fn (Assert $page) => $page
            ->where('massSchedules.data.0.name', 'Misa di paroki')
            ->where('massSchedules.data.1.name', 'Misa di kapel')
            ->where('massSchedules.data.2.name', 'Misa di stasi')
        );
});

it('sorts the special-mass list by title', function () {
    SpecialMass::factory()->for($this->location)->create(['title' => 'Zulu']);
    SpecialMass::factory()->for($this->location)->create(['title' => 'Alfa']);

    $this->actingAs(superAdmin())
        ->get(route('special-masses.index', ['sort' => 'title', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('specialMasses.data.0.title', 'Alfa')
        );
});

it('sorts the notice list by title', function () {
    ScheduleNotice::factory()->create(['title' => 'Zulu']);
    ScheduleNotice::factory()->create(['title' => 'Alfa']);

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.index', ['sort' => 'title', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notices.data.0.title', 'Alfa')
        );
});

it('echoes the sort back so the header can show the direction', function () {
    $this->actingAs(superAdmin())
        ->get(route('locations.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'name')
            ->where('filters.direction', 'desc')
        );
});

it('keeps the filters when a sort is applied', function () {
    Location::factory()->create(['name' => 'Zebra']);

    $this->actingAs(superAdmin())
        ->get(route('locations.index', [
            'search' => 'Zebra',
            'status' => 'active',
            'sort' => 'name',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'Zebra')
            ->where('filters.status', 'active')
            ->where('filters.sort', 'name')
            ->has('locations.data', 1)
        );
});

/*
|--------------------------------------------------------------------------
| What it does not do
|--------------------------------------------------------------------------
*/

it('ignores a sort key that is not on the whitelist', function () {
    Location::factory()->create(['name' => 'Zebra']);
    Location::factory()->create(['name' => 'Alpha']);

    // A sort key goes into ORDER BY, where it cannot be a bound parameter.
    // Anything outside the list has to be discarded rather than interpolated,
    // and discarded quietly: a mangled link shows a list rather than an error.
    $this->actingAs(superAdmin())
        ->get(route('locations.index', ['sort' => 'password']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations.data.0.name', 'Alpha')
        );
});

it('ignores a column the list does not show', function () {
    // 'status' on the notice list is a derived column (MASS-05 builds it from
    // three booleans), so there is nothing on disk to order by.
    ScheduleNotice::factory()->create();

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.index', ['sort' => 'status']))
        ->assertOk();
});

it('ignores a sort key whose column has been dropped', function () {
    MassSchedule::factory()->for($this->location)->create();

    // The 'Bahasa' column and its sort key were removed together. A stale link
    // or a bookmarked URL still carrying ?sort=language has to fall back to the
    // default order rather than reach ORDER BY for a column that no longer
    // exists, which MySQL answers with a 500 instead of an empty result.
    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list', ['sort' => 'language']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('massSchedules.data', 1)
        );
});

it('treats an unknown direction as ascending', function () {
    Location::factory()->create(['name' => 'Zebra']);
    Location::factory()->create(['name' => 'Alpha']);

    $this->actingAs(superAdmin())
        ->get(route('locations.index', ['sort' => 'name', 'direction' => 'sideways']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations.data.0.name', 'Alpha')
        );
});

/*
|--------------------------------------------------------------------------
| Pagination needs a total order
|--------------------------------------------------------------------------
*/

it('paginates without repeating or dropping a row', function () {
    Location::factory()->count(20)->create();

    /** @return list<array{id: int}> */
    $page = function (int $number): array {
        /** @var list<array{id: int}> $rows */
        $rows = $this->actingAs(superAdmin())
            ->get(route('locations.index', ['sort' => 'is_active', 'page' => $number]))
            ->viewData('page')['props']['locations']['data'];

        return $rows;
    };

    $first = collect($page(1))->pluck('id');
    $second = collect($page(2))->pluck('id');

    // Every row is live and the sort column is identical for all of them, so
    // this is the case where a missing tie-breaker shows: MySQL is free to
    // return the same row on both pages.
    expect($first->intersect($second))->toBeEmpty()
        ->and($first->count())->toBe(10)
        ->and($second->count())->toBe(10);
});

/*
|--------------------------------------------------------------------------
| The withCount/select trap
|--------------------------------------------------------------------------
*/

it('still counts the notices behind a schedule after the join was added', function () {
    ScheduleNotice::factory()->forSchedule(
        MassSchedule::factory()->for($this->location)->create(),
    )->create();

    // `select('mass_schedules.*')` is what makes "Lokasi" sortable, and it has
    // to come before withCount(): a later select() replaces the column list and
    // the counts vanish. Nothing errors — the delete dialog just reads an absent
    // field as "nothing points here", and reports zero forever.
    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('massSchedules.data.0.schedule_notices_count', 1)
        );
});

it('still counts the notices behind a special mass after the join was added', function () {
    $mass = SpecialMass::factory()->for($this->location)->create();
    ScheduleNotice::factory()->create(['special_mass_id' => $mass->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('special-masses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('specialMasses.data.0.schedule_notices_count', 1)
        );
});

/*
|--------------------------------------------------------------------------
| The join does not duplicate rows
|--------------------------------------------------------------------------
*/

it('counts each schedule once even though the query joins locations', function () {
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Senin, '07:30')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Selasa, '07:30')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Rabu, '07:30')->create();
    MassSchedule::factory()->for($this->location)->onDay(DayOfWeek::Kamis, '07:30')->create();

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('massSchedules.meta.total', 5)
            ->has('massSchedules.data', 5)
        );
});

it('still lists a schedule whose location was deactivated', function () {
    // leftJoin, not inner: a deactivated location must not make its schedules
    // vanish from the admin list — the admin still has to be able to switch it
    // back on from the edit form.
    $stasi = Location::factory()->create(['name' => 'Stasi Nonaktif']);
    $stasi->update(['is_active' => false]);

    MassSchedule::factory()->for($stasi)->create();

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('massSchedules.meta.total', 1)
        );
});

it('reports the form metadata the list still needs', function () {
    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.0.label', 'Minggu')
            ->where('locations.0.id', $this->location->id)
        );
});
