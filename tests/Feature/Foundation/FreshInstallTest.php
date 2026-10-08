<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Fresh install and seed
|--------------------------------------------------------------------------
|
| Test Matrix:
|
|   - T34 a fresh install plus the seeders produces a usable application
|
| RbacFoundationTest already covers SuperAdminSeeder::seedSuperAdmin() well:
| idempotence, a weak password, reactivation, the role, is_active. Every one of
| those calls the method directly, which is deliberate there so the tests do not
| have to touch process environment.
|
| What that leaves untested is the path a real installation takes:
| DatabaseSeeder::run() -> SuperAdminSeeder::run() -> config('admin.*'). That
| path is the one that decides whether `migrate:fresh --seed` works at all on a
| machine that has never filled in its own .env, and on CI, which never will.
| If SuperAdminSeeder::run() started throwing, every one of the tests above
| would still pass.
|
| "Fresh install" here means a freshly migrated, empty schema. It does not mean
| dropping every table, because RefreshDatabase wraps each test in a
| transaction that dropping the schema would destroy along with the isolation
| between tests.
|
| @see docs/DECISIONS.md D-14, D-17, D-27
|
*/

/**
 * Fill in the bootstrap credentials the way a configured .env would.
 */
function configureAdmin(?string $email = 'admin@paroki.test', string $password = 'Rahasia-Paroki-2026!'): void
{
    config([
        'admin.name' => 'Super Admin Paroki',
        'admin.email' => $email,
        'admin.password' => $password,
    ]);
}

/**
 * Undo configureAdmin() so one test cannot leak credentials into the next.
 */
function clearAdmin(): void
{
    config([
        'admin.name' => null,
        'admin.email' => null,
        'admin.password' => null,
    ]);
}

/*
|--------------------------------------------------------------------------
| The configured path
|--------------------------------------------------------------------------
*/

test('the full seed chain creates a usable super admin', function () {
    // T34. Goes through run() and config(), not the method RbacFoundationTest
    // calls directly.
    configureAdmin();

    $this->seed(DatabaseSeeder::class);

    $user = User::query()->where('email', 'admin@paroki.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Super Admin Paroki')
        // Phase 01 section 8.1 default plus Workstream 06 rule 2.
        ->and($user->is_active)->toBeTrue()
        // Workstream 06 rule 3. Hashed, and hashed in a way Laravel recognises,
        // so a plain comparison is enough and no hashing cost is paid twice.
        ->and($user->password)->not->toBe('Rahasia-Paroki-2026!')
        ->and(Hash::check('Rahasia-Paroki-2026!', $user->password))->toBeTrue()
        ->and($user->hasRole(AppServiceProvider::SUPER_ADMIN))->toBeTrue();
});

test('the seeded admin is immediately able to sign in', function () {
    // An account that exists but cannot authenticate is not a usable install.
    // Only possible because is_active is forced and the password is hashed, both
    // of which a plain seeder using updateOrCreate would have got wrong.
    configureAdmin();

    $this->seed(DatabaseSeeder::class);

    $this->post(route('login'), [
        'email' => 'admin@paroki.test',
        'password' => 'Rahasia-Paroki-2026!',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

test('the full chain produces the documented permission set', function () {
    // 15 permissions: 11 from roadmap section 10.4 plus admin.access, plus the
    // three media.* added in P10.
    configureAdmin();

    $this->seed(DatabaseSeeder::class);

    expect(Role::query()->pluck('name')->all())->toBe([AppServiceProvider::SUPER_ADMIN])
        ->and(Permission::query()->count())->toBe(15)
        ->and(Permission::query()->pluck('name')->all())->toContain(
            'admin.access',
            'settings.update',
            'media.view',
            'media.create',
            'media.delete',
        );
});

test('the full chain writes only the two seeded site settings', function () {
    configureAdmin();

    $this->seed(DatabaseSeeder::class);

    // PRD Lampiran D. Anything beyond this pair would be invented parish data,
    // which AGENTS.md forbids.
    expect(SiteSetting::query()->pluck('key')->sort()->values()->all())
        ->toBe(['parish_name', 'parish_short_name']);
});

test('seeding the full chain twice does not duplicate anything', function () {
    configureAdmin();

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(Role::query()->count())->toBe(1)
        ->and(Permission::query()->count())->toBe(15)
        ->and(SiteSetting::query()->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| The unconfigured path
|--------------------------------------------------------------------------
*/

test('the full chain succeeds with no admin credentials configured', function () {
    // This is the assertion that keeps `migrate:fresh --seed` working on a
    // machine nobody has set up and on CI. A teammate who has not filled in
    // their own .env must not be blocked by seeding.
    clearAdmin();

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0)
        // The rest of the chain still ran.
        ->and(Permission::query()->count())->toBe(15)
        ->and(SiteSetting::query()->count())->toBe(2);
});

test('a blank admin credential counts as absent', function () {
    // configured() uses filled(), so whitespace is not a credential either.
    config([
        'admin.name' => '  ',
        'admin.email' => '',
        'admin.password' => "\t",
    ]);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0);
});

test('a blank password with a real email creates no account', function () {
    // A half-configured .env is the dangerous case: an email with no password
    // must not produce an account nobody can sign in to.
    configureAdmin('admin@paroki.test');
    config(['admin.password' => null]);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0);
});

test('the full chain refuses a weak password', function () {
    // And the other half: credentials that are present but unacceptable must
    // stop the install rather than create a guessable account.
    configureAdmin('admin@paroki.test', 'rahasia');

    // Caught rather than asserted with toThrow() so the second assertion can
    // run: PHPUnit stops the test at the first unexpected exception, so
    // toThrow() would leave "no account was created" unchecked.
    $thrown = null;

    try {
        $this->seed(DatabaseSeeder::class);
    } catch (ValidationException $exception) {
        $thrown = $exception;
    }

    expect($thrown)->toBeInstanceOf(ValidationException::class)
        ->and(User::query()->count())->toBe(0);
});
