<?php

use App\Enums\DayOfWeek;
use App\Enums\LocationType;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Locations — /admin/lokasi
|--------------------------------------------------------------------------
|
| The lifecycle test that matters most here is deactivation: PRD 7.3 says the
| admin "nonaktifkan, tidak hapus", and there is no destroy route. The second is
| that a location's schedules disappear from the public page the moment it is
| switched off, which is MASS-07 and is enforced by the query rather than by
| whether the frontend bothered to check.
|
| Every test in this file needs the three middleware layers to be satisfied
| first, so most of them go through superAdmin() rather than a bare user.
*/

/*
|--------------------------------------------------------------------------
| Access
|--------------------------------------------------------------------------
*/

it('sends a guest to the login page', function (string $route) {
    $this->get($route)->assertRedirect(route('login'));
})->with([
    'index' => fn () => route('locations.index'),
    'create' => fn () => route('locations.create'),
]);

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('locations.index'))
        ->assertForbidden();
});

it('lets a super admin through', function () {
    $this->actingAs(superAdmin())
        ->get(route('locations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/jadwal-misa/lokasi'));
});

/*
|--------------------------------------------------------------------------
| Listing
|--------------------------------------------------------------------------
*/

it('lists locations with the active schedule count MASS-07 warns about', function () {
    $location = Location::factory()->create(['name' => 'Gereja Paroki']);
    // Distinct times: (location, day, start_time) is unique, so two schedules
    // at 07.30 on a Sunday are a duplicate by construction — which the factory
    // cannot know and the database will reject.
    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '10:00')->create();
    MassSchedule::factory()->for($location)->inactive()->onDay(DayOfWeek::Minggu, '12:00')->create();

    $this->actingAs(superAdmin())
        ->get(route('locations.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/jadwal-misa/lokasi')
            ->has('locations.data', 1)
            // The inactive one must not be counted: the dialog says how many
            // schedules will stop being published, and an inactive one already
            // has.
            ->where('locations.data.0.active_schedules_count', 2)
        );
});

it('searches locations by name', function () {
    Location::factory()->create(['name' => 'Gereja Paroki']);
    Location::factory()->create(['name' => 'Stasi Tanah Empang']);

    $this->actingAs(superAdmin())
        ->get(route('locations.index', ['search' => 'Empang']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('locations.data', 1)
            ->where('locations.data.0.name', 'Stasi Tanah Empang')
        );
});

it('filters by active state', function () {
    Location::factory()->create(['name' => 'Aktif']);
    Location::factory()->inactive()->create(['name' => 'Nonaktif']);

    $this->actingAs(superAdmin())
        ->get(route('locations.index', ['status' => 'inactive']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('locations.data', 1)
            ->where('locations.data.0.name', 'Nonaktif')
        );
});

/*
|--------------------------------------------------------------------------
| Creating
|--------------------------------------------------------------------------
*/

it('creates a location', function () {
    $this->actingAs(superAdmin())
        ->post(route('locations.store'), [
            'name' => 'Gereja Paroki',
            'slug' => 'gereja-paroki',
            'type' => LocationType::GerejaParoki->value,
            'address' => 'Jalan Contoh No. 1, Putussibau',
            'latitude' => null,
            'longitude' => null,
            'maps_url' => null,
            'notes' => null,
            'is_active' => true,
        ])
        ->assertRedirect(route('locations.index'))
        ->assertSessionHasNoErrors();

    expect(Location::query()->where('slug', 'gereja-paroki')->exists())->toBeTrue();
});

it('rejects a missing name, slug or type', function () {
    $this->actingAs(superAdmin())
        ->post(route('locations.store'), [])
        ->assertSessionHasErrors(['name', 'slug', 'type']);

    expect(Location::query()->count())->toBe(0);
});

it('rejects a duplicate slug', function () {
    Location::factory()->create(['slug' => 'gereja-paroki']);

    $this->actingAs(superAdmin())
        ->post(route('locations.store'), [
            'name' => 'Lain',
            'slug' => 'gereja-paroki',
            'type' => LocationType::Stasi->value,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('slug');
});

it('rejects a maps url that is not an http link', function (): void {
    $this->actingAs(superAdmin())
        ->post(route('locations.store'), [
            'name' => 'Gereja',
            'slug' => 'gereja',
            'type' => LocationType::GerejaParoki->value,
            // A link target that is not http(s) is not a link target.
            'maps_url' => 'javascript:alert(1)',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('maps_url');
});

it('accepts a plain http maps url', function (): void {
    $this->actingAs(superAdmin())
        ->post(route('locations.store'), [
            'name' => 'Gereja',
            'slug' => 'gereja',
            'type' => LocationType::GerejaParoki->value,
            'maps_url' => 'https://maps.app.goo.gl/example',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();
});

it('rejects half a coordinate pair', function (): void {
    // One coordinate alone builds a map link pointing at open ocean, so the
    // pair is validated together rather than per column.
    $this->actingAs(superAdmin())
        ->post(route('locations.store'), [
            'name' => 'Gereja',
            'slug' => 'gereja',
            'type' => LocationType::GerejaParoki->value,
            'latitude' => -0.0293,
            'longitude' => '',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('longitude');
});

it('accepts a full coordinate pair written with a decimal comma', function (): void {
    // The Indonesian keyboard, and faker id_ID, both produce this.
    $this->actingAs(superAdmin())
        ->post(route('locations.store'), [
            'name' => 'Gereja',
            'slug' => 'gereja',
            'type' => LocationType::GerejaParoki->value,
            'latitude' => '-0,0293000',
            'longitude' => '111,7686000',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect(Location::query()->value('latitude'))->toBe('-0.0293000');
});

it('rejects an out of range coordinate', function (): void {
    $this->actingAs(superAdmin())
        ->post(route('locations.store'), [
            'name' => 'Gereja',
            'slug' => 'gereja',
            'type' => LocationType::GerejaParoki->value,
            'latitude' => '120',
            'longitude' => '111',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('latitude');
});

/*
|--------------------------------------------------------------------------
| Updating — including deactivation, which is the whole lifecycle
|--------------------------------------------------------------------------
*/

it('updates a location', function () {
    $location = Location::factory()->create(['name' => 'Awal', 'slug' => 'awal']);

    $this->actingAs(superAdmin())
        ->patch(route('locations.update', $location), [
            'name' => 'Akhir',
            'slug' => 'awal',
            'type' => LocationType::Stasi->value,
            'is_active' => true,
        ])
        ->assertRedirect(route('locations.index'));

    expect($location->fresh())
        ->name->toBe('Akhir')
        ->type->toBe(LocationType::Stasi);
});

it('lets a location keep its own slug on update', function () {
    // Without the ignore() this fails against itself, which would make every
    // save of an untouched slug impossible.
    $location = Location::factory()->create(['slug' => 'gereja-paroki']);

    $this->actingAs(superAdmin())
        ->patch(route('locations.update', $location), [
            'name' => 'Gereja Paroki',
            'slug' => 'gereja-paroki',
            'type' => LocationType::GerejaParoki->value,
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();
});

it('deactivates a location through the ordinary update, keeping the row', function () {
    $location = Location::factory()->create();
    MassSchedule::factory()->for($location)->create();

    $this->actingAs(superAdmin())
        ->patch(route('locations.update', $location), [
            'name' => $location->name,
            'slug' => $location->slug,
            'type' => $location->type->value,
            'is_active' => false,
        ])
        ->assertRedirect(route('locations.index'));

    // MASS-04: not deleted, just switched off.
    expect($location->fresh())
        ->is_active->toBeFalse();

    expect(Location::query()->count())->toBe(1);
});

it('hides a deactivated location and its schedules from the public page (MASS-07)', function () {
    $location = Location::factory()->create();
    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularMasses', 1)
            ->has('locations', 1)
        );

    $location->update(['is_active' => false]);

    // Same request, different state. Nothing is cached (D-35), so this is the
    // only thing that has to change for the page to be correct.
    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularMasses', 0)
            ->has('locations', 0)
        );
});

/*
|--------------------------------------------------------------------------
| The map link, built server-side (D-8)
|--------------------------------------------------------------------------
*/

it('prefers maps_url over the coordinates', function () {
    $location = Location::factory()
        ->withCoordinates(-0.5, 111.5)
        ->create(['maps_url' => 'https://maps.example/paroki']);

    expect($location->mapsUrl())->toBe('https://maps.example/paroki');
});

it('builds a map link from the coordinates when there is no maps_url', function () {
    $location = Location::factory()->withCoordinates(-0.0293, 111.7686)->create();

    expect($location->mapsUrl())
        ->toBe('https://www.google.com/maps/search/?api=1&query=-0.0293000,111.7686000');
});

it('returns no map link when there is neither a url nor a pair', function () {
    expect(Location::factory()->withoutMaps()->create()->mapsUrl())->toBeNull();
});

it('never builds a map link from an address alone', function () {
    // A partial address produces a link to the wrong building, which is worse
    // than no button at all (roadmap 7.2).
    $location = Location::factory()->withoutMaps()->create(['address' => 'Jl. Merdeka No. 1']);

    expect($location->mapsUrl())->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Editing
|--------------------------------------------------------------------------
*/

it('renders the edit form with the location', function () {
    $location = Location::factory()->create(['name' => 'Gereja Paroki']);

    $this->actingAs(superAdmin())
        ->get(route('locations.edit', $location))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/jadwal-misa/lokasi/ubah')
            ->where('location.name', 'Gereja Paroki')
            // The form's Indonesian labels come from the language file rather
            // than a second copy in TypeScript.
            ->has('labels')
            ->has('hints')
            ->has('types')
        );
});

it('404s for a location that does not exist', function () {
    $this->actingAs(superAdmin())
        ->get(route('locations.edit', 999999))
        ->assertNotFound();
});
