<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminAccess;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Admin area access (Phase 01 Workstream 07 + 08)
|--------------------------------------------------------------------------
|
| PRD AUTH-R1 requires three layers on every /admin/* route: an authenticated
| session, an active account (D-08), and authorization. This file covers those
| three, plus the prefix move and the removal of public registration.
|
| Test Matrix covered here:
|
|   - T11 guest cannot open the admin area
|   - T12 a user without super_admin gets 403
|   - T13 a deactivated account cannot log in
|   - T14 registration is not available
|
| T15 (login rate limiting) is already covered in AuthenticationTest.
|
*/

test('the admin routes live under the admin prefix', function () {
    expect(route('login', absolute: false))->toStartWith('/admin/login')
        ->and(route('password.request', absolute: false))->toStartWith('/admin/forgot-password')
        ->and(route('logout', absolute: false))->toStartWith('/admin/logout')
        ->and(route('dashboard', absolute: false))->toBe('/admin')
        ->and(route('profile.edit', absolute: false))->toBe('/admin/akun/profile')
        ->and(route('security.edit', absolute: false))->toBe('/admin/akun/security');
});

test('a guest is redirected to the admin login page', function () {
    // T11
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->get(route('profile.edit'))->assertRedirect(route('login'));

    $this->get(route('security.edit'))->assertRedirect(route('login'));
});

test('a guest cannot see the login screen redirect loop', function () {
    // The login page itself must stay reachable, otherwise a guest would be
    // bounced forever.
    $this->get(route('login'))->assertOk();
});

test('a signed in user without the super admin role is forbidden', function () {
    // T12
    seedRolesAndPermissions();

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('profile.edit'))->assertForbidden();
});

test('a super admin can open the admin area', function () {
    $user = superAdmin();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('a deactivated account cannot log in', function () {
    // T13. Rejected at the login pipeline, so no session is ever created.
    seedRolesAndPermissions();

    $user = User::factory()->inactive()->create([
        'email' => 'nonaktif@example.test',
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('the inactive error message is in Indonesian', function () {
    $user = User::factory()->inactive()->create([
        'email' => 'nonaktif@example.test',
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'Akun ini dinonaktifkan. Hubungi pengelola paroki untuk mengaktifkannya kembali.',
    ]);
});

test('a deactivated session is thrown out of the admin area', function () {
    // The middleware is the authoritative layer: an account deactivated while
    // already signed in must not keep its access. This is the path that also
    // covers passkey logins and completing a two-factor challenge.
    $user = superAdmin();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $response = $this->actingAs($user->fresh())->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('an active account with no role still cannot enter', function () {
    // Guards against the is_active check accidentally standing in for the
    // permission check.
    seedRolesAndPermissions();

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
});

test('registration is not available', function () {
    // T14 / AUTH-R2. The feature is removed, so the routes do not exist at all.
    expect(Route::has('register'))->toBeFalse()
        ->and(Route::has('register.store'))->toBeFalse();

    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});

test('the admin gate uses a permission rather than a role name', function () {
    // AUTH-R3: Spatie is the source of truth. The Gate::before grant means a
    // super admin passes admin.access, and nobody else does.
    $superAdmin = superAdmin();

    expect($superAdmin->can('admin.access'))->toBeTrue();

    seedRolesAndPermissions();
    $plain = User::factory()->create();

    expect($plain->can('admin.access'))->toBeFalse();
});

test('every admin page is protected by the three layers', function () {
    // A structural guard: if someone adds an admin page without the
    // middleware, this catches it. Phase 01 13 lists all three as required.
    //
    // Scoped to the CMS routes rather than everything under the admin prefix,
    // because Fortify's own authentication routes also live there and must stay
    // reachable by guests (/admin/login, /admin/forgot-password, and so on).
    $adminPages = [
        'dashboard',
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'security.edit',
        'user-password.update',
        'appearance.edit',
    ];

    foreach ($adminPages as $name) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull("route {$name} tidak ada");

        $middleware = $route->gatherMiddleware();

        expect($middleware)
            ->toContain('auth')
            ->toContain(EnsureAccountIsActive::class)
            ->toContain(EnsureAdminAccess::class);
    }
});

test('the super admin role is the only role on the MVP', function () {
    seedRolesAndPermissions();

    expect(AppServiceProvider::SUPER_ADMIN)->toBe('super_admin')
        ->and(Role::query()->count())->toBe(1);
});
