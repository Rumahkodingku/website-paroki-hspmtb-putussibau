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
| The Informasi Profil page was removed in D-69, so the module root now
| redirects to the Sejarah page and the profile edit route no longer exists.
*/

it('sends a guest to the login page', function (string $route) {
    $this->get($route)->assertRedirect(route('login'));
})->with([
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

it('redirects the module root to the first section rather than rendering a page', function () {
    // There is no overview page (D-52); the root is a named redirect.
    $this->actingAs(superAdmin())
        ->get(route('parish-profile.index'))
        ->assertRedirect(route('parish-histories.index'));
});

it('never declares a public profil route twice', function () {
    $this->get(route('profil.sejarah', absolute: false))->assertOk();

    // The Informasi Profil summary page and Visi & Misi are both gone and must
    // not come back.
    expect(collect(app('router')->getRoutes()->getRoutes())
        ->pluck('action.as')
        ->filter()
        ->contains('profil.visiMisi'))->toBeFalse();

    expect(collect(app('router')->getRoutes()->getRoutes())
        ->pluck('action.as')
        ->filter()
        ->contains('profil.index'))->toBeFalse();
});
