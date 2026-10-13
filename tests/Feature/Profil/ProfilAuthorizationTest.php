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
*/

it('sends a guest to the login page', function (string $route) {
    $this->get($route)->assertRedirect(route('login'));
})->with([
    'profile edit' => fn () => route('parish-profile.edit'),
    'history list' => fn () => route('parish-histories.index'),
    'service area' => fn () => route('parish-service-area.edit'),
    'pastors' => fn () => route('pastors.index'),
    'periods' => fn () => route('board-periods.index'),
]);

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('parish-profile.edit'))
        ->assertForbidden();
});

it('lets a super admin through', function () {
    $this->actingAs(superAdmin())
        ->get(route('parish-profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/profil/informasi/index'));
});

it('redirects the module root to the first section rather than rendering a page', function () {
    // There is no overview page (D-52); the root is a named redirect.
    $this->actingAs(superAdmin())
        ->get(route('parish-profile.index'))
        ->assertRedirect(route('parish-profile.edit'));
});

it('never declares a public profil route twice', function () {
    $this->get(route('profil.index', absolute: false))->assertOk();
    $this->get(route('profil.sejarah', absolute: false))->assertOk();

    // Visi & Misi was removed in Phase 6A and must not come back.
    expect(collect(app('router')->getRoutes()->getRoutes())
        ->pluck('action.as')
        ->filter()
        ->contains('profil.visiMisi'))->toBeFalse();
});
