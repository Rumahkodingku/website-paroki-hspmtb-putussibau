<?php

use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to "expectations" methods that you can use to assert
| different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may always add your own helper functions to
| this file. By default, we're exposing some global helper functions.
|
*/

/**
 * Seed the system role and permissions, then drop Spatie's cache.
 *
 * Spatie registers permissions with the Gate when the application boots and
 * caches the result. phpunit.xml sets CACHE_STORE=array, which lives for the
 * whole test process, so a permission seeded inside a test is invisible unless
 * the cache is dropped afterwards. Without this, authorization tests would
 * silently pass or fail against a stale permission set.
 */
function seedRolesAndPermissions(): void
{
    test()->seed(PermissionSeeder::class);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

/**
 * Create a user that may enter /admin/*.
 *
 * Every route under the admin prefix requires the admin.access permission
 * (PRD AUTH-R1), so a plain factory user gets a 403 there. Tests that exercise
 * admin pages need this instead.
 */
function superAdmin(array $attributes = []): User
{
    seedRolesAndPermissions();

    $user = User::factory()->create($attributes);

    $user->assignRole(AppServiceProvider::SUPER_ADMIN);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}
