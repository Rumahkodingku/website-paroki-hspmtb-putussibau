<?php

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

/**
 * app:e2e:prepare exists because migrate:fresh drops every table it finds, so
 * these tests are about where it writes rather than about what it seeds.
 *
 * @see docs/DECISIONS.md D-29
 */
describe('app:e2e:prepare', function () {
    beforeEach(function () {
        config()->set('e2e.auth_file', storage_path('framework/testing/e2e-auth.json'));
    });

    it('refuses to run when no test database is configured', function () {
        config()->set('e2e.database', null);

        $this->artisan('app:e2e:prepare')
            ->expectsOutputToContain('e2e.database is not configured')
            ->assertFailed();
    });

    /**
     * The central property: a developer running `npm run e2e` on a machine whose
     * environment names a development database must not lose that data, and the
     * only way to guarantee it is for the command to ignore the environment
     * rather than trust whoever invoked it.
     *
     * The stand-in database name deliberately does not exist. If this ever
     * regressed to migrating the environment's database, migrate:fresh would
     * fail loudly on a missing database instead of quietly dropping somebody's
     * real tables, and the failure would name the bug.
     */
    it('rebuilds the suite database rather than the one the environment names', function () {
        config()->set('database.connections.mysql.database', 'website_paroki_hspmtb_env_placeholder');
        config()->set('e2e.database', 'website_paroki_hspmtb_test');

        $this->artisan('app:e2e:prepare')
            ->expectsOutputToContain('Ignored .env database')
            ->assertSuccessful();

        // Freshly migrated and seeded: exactly the one seeded account, which
        // could only be read from the suite database.
        expect(User::query()->count())->toBe(1)
            ->and(DB::connection()->getDatabaseName())->toBe('website_paroki_hspmtb_test');
    });

    it('seeds a super admin that can actually sign in', function () {
        $this->artisan('app:e2e:prepare')->assertSuccessful();

        $admin = User::query()
            ->where('email', config('e2e.admin.email'))
            ->sole();

        expect($admin->is_active)->toBeTrue()
            ->and($admin->email_verified_at)->not->toBeNull()
            ->and($admin->hasRole(AppServiceProvider::SUPER_ADMIN))->toBeTrue()
            ->and(Hash::check(config('e2e.admin.password'), $admin->password))->toBeTrue();
    });

    it('writes the credentials the specs read, so the two cannot drift', function () {
        $this->artisan('app:e2e:prepare')->assertSuccessful();

        // File::get() rather than file_get_contents(): the latter is string|false
        // and a false would turn into a confusing json_decode() type error
        // instead of naming the file that was missing.
        $auth = json_decode(
            File::get(storage_path('framework/testing/e2e-auth.json')),
            true,
        );

        // The database travels in the same file because the web server has to be
        // pointed at the one that was just seeded.
        expect($auth)->toBe([
            'email' => config('e2e.admin.email'),
            'password' => config('e2e.admin.password'),
            'database' => config('e2e.database'),
        ]);
    });
});
