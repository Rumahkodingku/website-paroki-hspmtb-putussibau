<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Profil Paroki — akses
|--------------------------------------------------------------------------
|
| Empat lapis middleware /admin plus Policy. Setiap test admin memakai
| superAdmin() supaya 403 yang muncul memang dari Policy, bukan dari gate
| admin.access.
|
| Informasi Profil is the module root again (D-78), so the module root redirect
| lands there instead of on Sejarah.
*/

it('sends a guest to the login page', function (string $route) {
    $this->get($route)->assertRedirect(route('login'));
})->with([
    'profile' => fn () => route('parish-profile.edit'),
    'history list' => fn () => route('parish-histories.index'),
    'service area' => fn () => route('parish-service-area.edit'),
    'pastors' => fn () => route('pastors.index'),
    'periods' => fn () => route('board-periods.index'),
]);

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('parish-histories.index'))
        ->assertForbidden();
});

it('lets a super admin through', function () {
    $this->actingAs(superAdmin())
        ->get(route('parish-histories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/profil/sejarah/index'));
});

it('redirects the module root to the profile page', function () {
    // The root is a named redirect; Informasi Profil is the first section.
    $this->actingAs(superAdmin())
        ->get(route('parish-profile.index'))
        ->assertRedirect(route('parish-profile.edit'));
});

it('declares the summary and never brings Visi & Misi back', function () {
    $this->get(route('profil.index', absolute: false))->assertOk();
    $this->get(route('profil.sejarah', absolute: false))->assertOk();

    expect(collect(app('router')->getRoutes()->getRoutes())
        ->pluck('action.as')
        ->filter()
        ->contains('profil.index'))->toBeTrue();

    expect(collect(app('router')->getRoutes()->getRoutes())
        ->pluck('action.as')
        ->filter()
        ->contains('profil.visiMisi'))->toBeFalse();
});
