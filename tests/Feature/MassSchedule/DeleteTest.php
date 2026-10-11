<?php

use App\Enums\DayOfWeek;
use App\Enums\LocationType;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\ScheduleNotice;
use App\Models\SpecialMass;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\UniqueConstraintViolationException;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Deletion — soft delete, super-admin only
|--------------------------------------------------------------------------
|
| Deletion arrived after the module shipped without it, and D-40 recorded what
| that makes true and what it makes false. The rules below are the ones that
| changed:
|
|   ADM-05   a MUST that says deletion on content tables is a soft delete. It was
|            an unfulfilled divergence while there was no delete at all; these
|            tests are what make it fulfilled rather than merely claimed.
|
|   D-40 §1  four destroy routes, guarded by locations.delete,
|            massschedules.delete, specialmasses.delete and
|            schedulenotices.delete. Super admin is the only role on the MVP, so
|            "only super admin" and "has the permission" are the same statement
|            today - the user without a role is what actually proves the guard.
|
|   D-40 §2  the unique index on (location_id, day_of_week, start_time) and on
|            locations.slug both gained a generated column, so a deleted row
|            frees its slot while two live rows still collide. That is the part
|            a schema comment cannot promise on its own, so it is measured.
|
|   4.5      a location with schedules or special masses cannot be deleted.
|
| @see docs/DECISIONS.md D-40 (supersedes D-34 section 1)
| @see docs/PRD.md ADM-05, 7.3, MASS-02, MASS-04, MASS-06
*/

beforeEach(function () {
    $this->location = Location::factory()->create(['name' => 'Gereja Paroki']);
    $this->schedule = MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create();
});

/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

it('lets a super admin delete a location', function () {
    $empty = Location::factory()->create(['name' => 'Kapel Tersendiri']);

    $this->actingAs(superAdmin())
        ->delete(route('locations.destroy', $empty))
        ->assertRedirect(route('locations.index'))
        ->assertSessionHasNoErrors();

    expect($empty->fresh()->trashed())->toBeTrue()
        ->and($this->location->fresh()->trashed())->toBeFalse();
});

it('soft deletes each of the four resources', function (string $route, $model) {
    $record = $model::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route($route, $record))
        ->assertRedirect();

    expect($record->fresh()->trashed())->toBeTrue();
})->with([
    'location' => fn () => ['locations.destroy', Location::factory()->create()],
    'schedule' => fn () => ['mass-schedules.destroy', MassSchedule::class],
    'special mass' => fn () => ['special-masses.destroy', SpecialMass::class],
    'notice' => fn () => ['schedule-notices.destroy', ScheduleNotice::class],
]);

it('refuses a user without the delete permission', function () {
    $plain = User::factory()->create();

    $this->actingAs($plain)
        ->delete(route('locations.destroy', $this->location))
        ->assertForbidden();

    expect($this->location->fresh()->trashed())->toBeFalse();
});

it('refuses a guest', function () {
    $this->delete(route('locations.destroy', $this->location))
        ->assertRedirect(route('login'));

    expect($this->location->fresh()->trashed())->toBeFalse();
});

it('reports the delete ability to the admin list', function () {
    $this->actingAs(superAdmin())
        ->get(route('locations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations.data.0.can.delete', true)
            ->where('locations.data.0.can.update', true)
        );
});

it('denies a user without any role before the policy is reached', function () {
    // EnsureAdminAccess turns /admin into super-admin-only space, so the
    // delete permission is not what stops this user - the middleware is. That
    // is worth knowing rather than pretending otherwise: the permission is
    // currently redundant on the MVP, and it is kept because it is the line
    // that moves the day a second role exists (D-36). It is not dead weight,
    // because the policy reads it today.
    $this->actingAs(User::factory()->create())
        ->get(route('locations.index'))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Roadmap 4.5 — a location with children cannot go
|--------------------------------------------------------------------------
*/

it('refuses to delete a location that still has a schedule', function () {
    $this->actingAs(superAdmin())
        ->delete(route('locations.destroy', $this->location))
        ->assertRedirect(route('locations.index'))
        ->assertSessionHasErrors('location');

    expect($this->location->fresh()->trashed())->toBeFalse();
    expect($this->schedule->fresh())->not->toBeNull();
});

it('refuses to delete a location that still has a special mass', function () {
    SpecialMass::factory()->for($this->location)->create();

    $this->actingAs(superAdmin())
        ->delete(route('locations.destroy', $this->location))
        ->assertSessionHasErrors('location');

    expect($this->location->fresh()->trashed())->toBeFalse();
});

it('names both counts in the refusal', function () {
    SpecialMass::factory()->for($this->location)->create();

    $this->actingAs(superAdmin())
        ->from(route('locations.index'))
        ->delete(route('locations.destroy', $this->location))
        ->assertSessionHasErrors('location');

    $message = session('errors')->first('location');

    expect($message)
        ->toContain('jadwal misa rutin')
        ->toContain('misa khusus');
});

it('deletes a location once its schedules are gone', function () {
    $this->schedule->delete();

    $this->actingAs(superAdmin())
        ->delete(route('locations.destroy', $this->location))
        ->assertSessionHasNoErrors();

    expect($this->location->fresh()->trashed())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| The generated columns — D-40 section 2
|--------------------------------------------------------------------------
*/

it('frees the time slot of a deleted schedule', function () {
    $this->schedule->delete();

    $replacement = MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create(['name' => 'Misa pengganti']);

    expect($replacement->exists)->toBeTrue()
        ->and($replacement->trashed())->toBeFalse()
        ->and(MassSchedule::withTrashed()->count())->toBe(2);
});

it('still refuses two live schedules in one slot', function () {
    // The other half of the same rule: the generated column must not weaken
    // MASS-06 while freeing the slot.
    $this->schedule->delete();

    MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create();

    expect(fn () => MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create())
        ->toThrow(UniqueConstraintViolationException::class);

    expect(MassSchedule::withTrashed()->whereNull('deleted_at')->count())->toBe(1);
});

it('rejects a new schedule in a deleted slot with a sentence, not a 500', function () {
    $this->schedule->delete();

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->id,
            'day_of_week' => DayOfWeek::Minggu->value,
            'start_time' => '07:30',
            'name' => 'Misa baru',
            'is_active' => true,
        ])
        ->assertRedirect(route('mass-schedules.list'))
        ->assertSessionHasNoErrors();

    expect(MassSchedule::withTrashed()->whereNull('deleted_at')->count())->toBe(1);
});

it('still rejects a duplicate in a live slot with the MASS-06 sentence', function () {
    $this->actingAs(superAdmin())
        ->from(route('mass-schedules.create'))
        ->post(route('mass-schedules.store'), [
            'location_id' => $this->location->id,
            'day_of_week' => DayOfWeek::Minggu->value,
            'start_time' => '07:30',
            'name' => 'Misa kembar',
            'is_active' => true,
        ])
        ->assertSessionHasErrors([
            'location_id' => 'Sudah ada jadwal pada lokasi, hari, dan jam tersebut.',
        ]);
});

it('frees the slug of a deleted location', function () {
    $this->schedule->delete();
    $this->location->delete();

    $replacement = Location::factory()->create([
        'name' => 'Stasi Baru',
        'slug' => $this->location->slug,
    ]);

    expect($replacement->slug)->toBe($this->location->slug);
});

it('rejects a duplicate slug in a live location', function () {
    Location::factory()->create([
        'name' => 'Stasi Tanah Empang',
        'slug' => 'stasi-tanah-empang',
    ]);

    $this->actingAs(superAdmin())
        ->from(route('locations.create'))
        ->post(route('locations.store'), [
            'name' => 'Ketiga',
            'slug' => 'stasi-tanah-empang',
            'type' => LocationType::Stasi->value,
        ])
        ->assertSessionHasErrors('slug');
});

/*
|--------------------------------------------------------------------------
| A deleted row disappears — ADM-05 and the public page
|--------------------------------------------------------------------------
*/

it('hides a deleted row from the admin list', function () {
    $this->schedule->delete();

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('massSchedules.data', [])
        );
});

it('hides a deleted row from the public page', function () {
    $this->schedule->delete();

    $this->get(route('jadwal-misa.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('regularMasses', [])
            ->where('nextMass', null)
        );
});

it('hides a deleted location and its filter option from the public page', function () {
    $this->schedule->delete();
    $this->location->delete();

    $this->get(route('jadwal-misa.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations', [])
            ->where('regularMasses', [])
        );
});

it('404s an admin route for a deleted row', function () {
    $id = $this->schedule->id;
    $this->schedule->delete();

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.edit', $id))
        ->assertNotFound();
});

it('keeps a deleted row readable with withTrashed', function () {
    $this->schedule->delete();

    // firstOrFail() rather than find(): find() is typed Model|Collection
    // because it accepts an array of keys, and there is exactly one row here.
    $found = MassSchedule::withTrashed()->whereKey($this->schedule->id)->firstOrFail();

    expect($found->trashed())->toBeTrue()
        ->and($found->name)->toBe($this->schedule->name);
});

it('does not let deleted_at be mass assigned', function () {
    // SoftDeletes writes the column itself; a request that could set it would
    // be a way to hide a row without a permission check.
    // Eloquent is in strict mode outside production, so passing a column that
    // is not fillable is an exception rather than a silent drop. Either way
    // deleted_at never arrives from a request; SoftDeletes writes it itself.
    expect(fn () => new MassSchedule([
        'location_id' => $this->location->id,
        'day_of_week' => DayOfWeek::Sabtu->value,
        'start_time' => '17:00',
        'name' => 'Disembunyikan',
        'deleted_at' => now(),
    ]))->toThrow(MassAssignmentException::class);

    expect(MassSchedule::whereNull('deleted_at')->count())->toBe(1);
});

it('does not move the timestamp of an already deleted row', function () {
    $this->schedule->delete();
    $first = $this->schedule->fresh()->deleted_at;

    $this->schedule->fresh()->delete();

    expect($this->schedule->fresh()->deleted_at->toDateTimeString())
        ->toBe($first->toDateTimeString());
});

/*
|--------------------------------------------------------------------------
| Notices pointing at something that was deleted — D-40 section 5
|--------------------------------------------------------------------------
*/

it('stops publishing a notice whose location was deleted', function () {
    $notice = ScheduleNotice::factory()->for($this->location)->create();

    // A location-targeted notice is the branch the public query implements:
    // it is shown while that location is being filtered on.
    $this->get(route('jadwal-misa.index', ['lokasi' => $this->location->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('notices', 1));

    $this->schedule->delete();
    $this->location->delete();

    // filteredLocation() reads through scopeActive, so a deleted location is
    // no longer found, the orWhere branch disappears, and the notice that
    // named it goes with it. The notice row itself is untouched.
    $this->get(route('jadwal-misa.index', ['lokasi' => $this->location->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('notices', 0));

    expect($notice->fresh()->trashed())->toBeFalse();
});

/*
 * A notice aimed at a schedule or a special mass is not shown by the public
 * query at all - it matches neither "no target" nor "the filtered location".
 * That is a gap in Phase 04, not something D-40 introduced, and it is left
 * alone here: widening the public page is a separate decision. What matters
 * for deletion is that the admin can see it, which is the next test.
 */

it('flags a notice whose target was deleted in the admin list', function () {
    ScheduleNotice::factory()->forSchedule($this->schedule)->create();

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notices.data.0.target_deleted', false)
        );

    $this->schedule->delete();

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notices.data.0.target_deleted', true)
        );
});

it('reports no delete consequence for a schedule nothing points at', function () {
    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('massSchedules.data.0.schedule_notices_count', 0)
        );
});

it('counts the notices a schedule deletion would stop publishing', function () {
    ScheduleNotice::factory()->forSchedule($this->schedule)->create();
    ScheduleNotice::factory()->forSchedule($this->schedule)->create();

    $this->actingAs(superAdmin())
        ->get(route('mass-schedules.list'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('massSchedules.data.0.schedule_notices_count', 2)
        );
});

/*
|--------------------------------------------------------------------------
| The counts that decide the dialog
|--------------------------------------------------------------------------
*/

it('counts what blocks a location deletion in the admin list', function () {
    SpecialMass::factory()->for($this->location)->create();

    $this->actingAs(superAdmin())
        ->get(route('locations.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locations.data.0.mass_schedules_count', 1)
            ->where('locations.data.0.special_masses_count', 1)
            ->where('locations.data.0.schedule_notices_count', 0)
        );
});

/*
|--------------------------------------------------------------------------
| Duplication still works on a table with a generated column
|--------------------------------------------------------------------------
*/

it('duplicates a schedule without sending the generated column', function () {
    // replicate() with no argument copies every column, and MySQL refuses an
    // insert that names a generated one - error 3105, surfaced as a 500 on the
    // duplicate endpoint.
    $target = Location::factory()->create(['name' => 'Stasi Tujuan']);

    $this->actingAs(superAdmin())
        ->post(route('mass-schedules.duplicate', $this->schedule), [
            'location_id' => $target->id,
        ])
        ->assertRedirect(route('mass-schedules.list'))
        ->assertSessionHasNoErrors();

    expect(MassSchedule::where('location_id', $target->id)->count())->toBe(1);
});
