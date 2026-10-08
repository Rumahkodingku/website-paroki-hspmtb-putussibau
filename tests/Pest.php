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

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Refresh database
|--------------------------------------------------------------------------
|
| Only the feature tests need a clean database. Unit tests are scoped to one
| class and are not allowed to touch storage, so booting a transaction they
| never use is a cost paid by every one of them.
|
*/

pest()->use(RefreshDatabase::class)->in('Feature');

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
/**
 * Allocate a colour on a truecolor image.
 *
 * GD declares imagecolorallocate() as returning int|false, the false case
 * being palette images running out of colours. On a truecolor image it cannot
 * happen, and these fixtures only ever use truecolor, so the check exists to
 * turn a silently wrong fixture into a loud one rather than to cast a cast away.
 *
 * Lives here rather than in a test file because MediaTest and FailedJobTest
 * both need it and a helper defined in one test file is not loaded when the
 * other runs on its own.
 *
 * @param  int<0, 255>  $r
 * @param  int<0, 255>  $g
 * @param  int<0, 255>  $b
 */
function truecolor(GdImage $image, int $r, int $g, int $b): int
{
    $colour = imagecolorallocate($image, $r, $g, $b);

    if ($colour === false) {
        throw new RuntimeException('imagecolorallocate gagal pada truecolor image.');
    }

    return $colour;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function superAdmin(array $attributes = []): User
{
    seedRolesAndPermissions();

    $user = User::factory()->create($attributes);

    $user->assignRole(AppServiceProvider::SUPER_ADMIN);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}
