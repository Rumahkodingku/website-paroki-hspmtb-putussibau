<?php

use App\Enums\DayOfWeek;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\ScheduleNotice;
use App\Models\SpecialMass;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Schedule notices — /admin/pemberitahuan-jadwal
|--------------------------------------------------------------------------
|
| MASS-05 has three rejection conditions and one acceptance, and they are the
| whole file: hidden when is_active is false, when starts_at is still ahead, or
| when now is past ends_at; shown when ends_at is null, which means no upper
| bound rather than no window.
|
| The target exclusivity is the other thing worth pinning. A notice pointing at
| both a location and a schedule inside it has no single reading — does it apply
| at that location, or to that schedule everywhere? — so the form request refuses
| the combination rather than the banner guessing (D-34).
|
| @see docs/DECISIONS.md D-34
*/

beforeEach(function () {
    $this->location = Location::factory()->create();
});

/*
|--------------------------------------------------------------------------
| Access
|--------------------------------------------------------------------------
*/

it('sends a guest from the list', function () {
    $this->get(route('schedule-notices.index'))->assertRedirect(route('login'));
});

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('schedule-notices.index'))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Creating
|--------------------------------------------------------------------------
*/

it('creates a general notice', function () {
    $this->actingAs(superAdmin())
        ->post(route('schedule-notices.store'), [
            'title' => 'Perubahan Jadwal',
            'message' => 'Misa dipindahkan ke kapel.',
            'location_id' => null,
            'mass_schedule_id' => null,
            'special_mass_id' => null,
            'starts_at' => now()->toDateString().'T08:00',
            'ends_at' => now()->addWeek()->toDateString().'T20:00',
            'is_active' => true,
        ])
        ->assertRedirect(route('schedule-notices.index'))
        ->assertSessionHasNoErrors();

    $stored = ScheduleNotice::query()->firstOrFail();

    expect($stored)->isGeneral()->toBeTrue()
        ->and($stored->targetKind())->toBeNull();
});

it('creates a notice targeted at a location', function () {
    $this->actingAs(superAdmin())
        ->post(route('schedule-notices.store'), [
            'title' => 'Stasi tutup',
            'message' => 'Stasi tutup minggu ini.',
            'location_id' => $this->location->getKey(),
            'mass_schedule_id' => null,
            'special_mass_id' => null,
            'starts_at' => now()->toDateString().'T08:00',
            'ends_at' => null,
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect(ScheduleNotice::query()->firstOrFail())->isGeneral()->toBeFalse();
});

it('refuses a notice with two targets at once', function () {
    // The form offers one picker, so this is what a hand-written request looks
    // like. Refusing is the point: the banner would have to pick a reading.
    $schedule = MassSchedule::factory()->for($this->location)->create();

    $this->actingAs(superAdmin())
        ->post(route('schedule-notices.store'), [
            'title' => 'Dua target',
            'message' => 'Tidak jelas berlaku di mana.',
            'location_id' => $this->location->getKey(),
            'mass_schedule_id' => $schedule->getKey(),
            'special_mass_id' => null,
            'starts_at' => now()->toDateString().'T08:00',
            'ends_at' => null,
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['mass_schedule_id']);

    expect(ScheduleNotice::query()->count())->toBe(0);
});

it('refuses a target that does not exist', function () {
    $this->actingAs(superAdmin())
        ->post(route('schedule-notices.store'), [
            'title' => 'Target hantu',
            'message' => 'Tidak ada.',
            'location_id' => 999999,
            'mass_schedule_id' => null,
            'special_mass_id' => null,
            'starts_at' => now()->toDateString().'T08:00',
            'ends_at' => null,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('location_id');
});

it('rejects a missing title or message', function () {
    $this->actingAs(superAdmin())
        ->post(route('schedule-notices.store'), [
            'starts_at' => now()->toDateString().'T08:00',
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['title', 'message']);

    expect(ScheduleNotice::query()->count())->toBe(0);
});

it('rejects an end before the start', function () {
    $this->actingAs(superAdmin())
        ->post(route('schedule-notices.store'), [
            'title' => 'Rentang terbalik',
            'message' => 'Tidak masuk akal.',
            'starts_at' => now()->addWeek()->toDateString().'T08:00',
            'ends_at' => now()->toDateString().'T08:00',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('ends_at');
});

/*
|--------------------------------------------------------------------------
| MASS-05 — which notices a visitor sees
|--------------------------------------------------------------------------
*/

it('shows a notice inside its window', function () {
    ScheduleNotice::factory()->general()->create(['title' => 'Tampil']);

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notices', 1)
            ->where('notices.0.title', 'Tampil')
        );
});

it('hides a notice that has not started yet', function () {
    ScheduleNotice::factory()->scheduled()->create();

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('notices', 0));
});

it('hides a notice whose window has closed', function () {
    ScheduleNotice::factory()->expired()->create();

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('notices', 0));
});

it('hides a notice that is switched off', function () {
    ScheduleNotice::factory()->inactive()->create();

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('notices', 0));
});

it('treats a missing end time as no upper bound', function () {
    ScheduleNotice::factory()->openEnded()->create(['starts_at' => now()->subYear()]);

    // It started a year ago and has no end. MASS-05 says a null ends_at means
    // "still being shown", not "never shown".
    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('notices', 1));
});

it('shows a notice again once its window opens', function () {
    $notice = ScheduleNotice::factory()->scheduled()->create();

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('notices', 0));

    $notice->update(['starts_at' => now()->subMinute()]);

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('notices', 1));
});

/*
|--------------------------------------------------------------------------
| Targeting on the public page
|--------------------------------------------------------------------------
*/

it('hides a location-targeted notice when no location is filtered', function () {
    // A notice about one location must not sit above an unfiltered list as
    // though it concerned every mass there.
    ScheduleNotice::factory()->create([
        'location_id' => $this->location->getKey(),
        'mass_schedule_id' => null,
        'special_mass_id' => null,
    ]);

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('notices', 0));
});

it('shows a location-targeted notice when that location is filtered', function () {
    ScheduleNotice::factory()->create([
        'location_id' => $this->location->getKey(),
        'mass_schedule_id' => null,
        'special_mass_id' => null,
    ]);

    $this->get(route('jadwal-misa.index', ['lokasi' => $this->location->getKey()]))
        ->assertInertia(fn (Assert $page) => $page->has('notices', 1));
});

it('still shows a general notice alongside a location filter', function () {
    ScheduleNotice::factory()->general()->create();
    ScheduleNotice::factory()->create([
        'location_id' => $this->location->getKey(),
        'mass_schedule_id' => null,
        'special_mass_id' => null,
    ]);

    $this->get(route('jadwal-misa.index', ['lokasi' => $this->location->getKey()]))
        ->assertInertia(fn (Assert $page) => $page->has('notices', 2));
});

/*
|--------------------------------------------------------------------------
| The admin list shows everything, with a derived status
|--------------------------------------------------------------------------
*/

it('lists every notice with its derived status', function () {
    ScheduleNotice::factory()->general()->create(['title' => 'Tampil']);
    ScheduleNotice::factory()->scheduled()->create(['title' => 'Terjadwal']);
    ScheduleNotice::factory()->expired()->create(['title' => 'Lewat']);
    ScheduleNotice::factory()->inactive()->create(['title' => 'Nonaktif']);

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.index'))
        ->assertInertia(fn (Assert $page) => $page->has('notices.data', 4));
});

it('derives the visible status from the window, not from a stored column', function () {
    ScheduleNotice::factory()->general()->create();

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notices.data.0.status', 'visible')
            ->where('notices.data.0.is_general', true)
        );
});

it('filters the admin list by status', function () {
    ScheduleNotice::factory()->general()->create();
    ScheduleNotice::factory()->expired()->create();

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.index', ['status' => 'expired']))
        ->assertInertia(fn (Assert $page) => $page->has('notices.data', 1));
});

it('offers past special masses as no notice target', function () {
    // A notice about a mass that already happened has nothing to announce.
    SpecialMass::factory()->for($this->location)->past()->create();
    SpecialMass::factory()->for($this->location)->upcoming()->create();

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/jadwal-misa/pemberitahuan/tambah')
            ->has('targets.special_masses', 1)
            ->has('targets.locations')
        );
});

/*
|--------------------------------------------------------------------------
| Updating
|--------------------------------------------------------------------------
*/

it('updates a notice', function () {
    $notice = ScheduleNotice::factory()->general()->create();

    $this->actingAs(superAdmin())
        ->patch(route('schedule-notices.update', $notice), [
            'title' => 'Judul baru',
            'message' => 'Isi baru.',
            'location_id' => null,
            'mass_schedule_id' => null,
            'special_mass_id' => null,
            'starts_at' => now()->toDateString().'T08:00',
            'ends_at' => null,
            'is_active' => true,
        ])
        ->assertRedirect(route('schedule-notices.index'))
        ->assertSessionHasNoErrors();

    expect($notice->fresh()->title)->toBe('Judul baru');
});

it('renders the edit form with the notice target restored', function () {
    $schedule = MassSchedule::factory()->for($this->location)
        ->onDay(DayOfWeek::Minggu, '07:30')->create();

    $notice = ScheduleNotice::factory()->create([
        'mass_schedule_id' => $schedule->getKey(),
        'location_id' => null,
        'special_mass_id' => null,
    ]);

    $this->actingAs(superAdmin())
        ->get(route('schedule-notices.edit', $notice))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/jadwal-misa/pemberitahuan/ubah')
            ->where('notice.mass_schedule_id', $schedule->getKey())
            ->where('notice.location_id', null)
            ->where('notice.target_kind', 'rutin')
        );
});
