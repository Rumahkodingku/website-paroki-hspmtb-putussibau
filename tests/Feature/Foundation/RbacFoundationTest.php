<?php

use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| RBAC foundation (Phase 01 Workstream 05)
|--------------------------------------------------------------------------
|
| Mengunci Phase 01 §10 dan Test Matrix T09 / T10 / T12:
|
|   - T09  role super_admin ada
|   - T10  user bisa menerima super_admin
|   - T12  user tanpa role ditolak
|   - §10.2  User memakai HasRoles
|   - §10.5  guard Spatie konsisten dengan guard autentikasi
|   - §10.7  tidak ada sumber otorisasi selain Spatie
|
| T11 (guest ditolak) dan T14 (registrasi tidak ada) milik P06 karena area
| /admin belum dibangun.
|
*/

test('the super_admin system role exists', function () {
    seedRolesAndPermissions();

    // T09
    expect(Role::query()->where('name', 'super_admin')->exists())->toBeTrue()
        ->and(Role::query()->count())->toBe(1);
});

test('the system role is seeded even when no admin credentials are configured', function () {
    // Phase 01 10.3 calls super_admin a system role, so it must not depend on
    // ADMIN_EMAIL being present.
    $this->seed(PermissionSeeder::class);

    expect(Role::query()->where('name', 'super_admin')->exists())->toBeTrue();
});

test('the foundation permissions are seeded with resource.action names', function () {
    seedRolesAndPermissions();

    // Verbatim from Phase 01 10.4, plus admin.access added in P06 as the gate for
    // /admin/* (D-19), plus the media trio added in P10 for the upload
    // endpoints (D-24).
    $expected = [
        'admin.access',

        'dashboard.view',
        'settings.view',
        'settings.update',
        'media.view',
        'media.create',
        'media.delete',
        'users.view',
        'users.create',
        'users.update',
        'users.disable',
        'posts.view',
        'posts.create',
        'posts.update',
        'posts.delete',
    ];

    expect(Permission::query()->pluck('name')->sort()->values()->all())
        ->toBe(collect($expected)->sort()->values()->all());

    // PRD AUTH-R6: every permission follows resource.action.
    foreach ($expected as $permission) {
        expect($permission)->toMatch('/^[a-z]+\.[a-z]+$/');
    }
});

test('a user can be assigned the super_admin role', function () {
    seedRolesAndPermissions();

    $user = User::factory()->create();
    $user->assignRole(AppServiceProvider::SUPER_ADMIN);

    // T10
    expect($user->fresh()->hasRole('super_admin'))->toBeTrue()
        ->and($user->fresh()->roles)->toHaveCount(1);
});

test('the super_admin role passes every gate check', function () {
    seedRolesAndPermissions();

    $user = User::factory()->create();
    $user->assignRole(AppServiceProvider::SUPER_ADMIN);

    // Gate::before grants everything, including permissions that do not exist.
    expect($user->can('posts.create'))->toBeTrue()
        ->and($user->can('settings.update'))->toBeTrue()
        ->and($user->can('permission.yang.tidak.ada'))->toBeTrue();
});

test('a user without the super_admin role is denied', function () {
    seedRolesAndPermissions();

    // T12. Since P06 the admin area exists, so this is no longer just a Gate
    // level check: a signed-in user without the role is turned away with a 403.
    $user = User::factory()->create();

    expect($user->can('posts.create'))->toBeFalse()
        ->and($user->can('settings.update'))->toBeFalse()
        ->and($user->can('permission.yang.tidak.ada'))->toBeFalse();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('an inactive super admin is still a super admin but cannot be filtered as active', function () {
    seedRolesAndPermissions();

    $user = User::factory()->inactive()->create();
    $user->assignRole(AppServiceProvider::SUPER_ADMIN);

    expect($user->can('posts.create'))->toBeTrue()
        ->and($user->fresh()->is_active)->toBeFalse()
        ->and(User::active()->whereKey($user->getKey())->exists())->toBeFalse();
});

test('roles and permissions use the same guard as authentication', function () {
    // Phase 01 10.5: no state where auth uses web while permissions use
    // another guard.
    seedRolesAndPermissions();

    $expected = config('auth.defaults.guard');

    expect($expected)->toBe('web')
        ->and(Role::query()->value('guard_name'))->toBe($expected)
        ->and(Permission::query()->value('guard_name'))->toBe($expected);

    $user = User::factory()->create();
    $user->assignRole(AppServiceProvider::SUPER_ADMIN);

    expect($user->fresh()->roles->first()->guard_name)->toBe($expected);
});

test('the user model has no role column or roles method of its own', function () {
    // Phase 01 10.7 and the Spatie prerequisite: users must not define a
    // role/roles property, column or method, and roles() is supplied by
    // HasRoles.
    expect(Schema::hasColumn('users', 'role'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'role_id'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'roles'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'permissions'))->toBeFalse();

    // The relation is provided by the trait, so it resolves to a morph relation
    // over the Spatie pivot rather than anything defined here.
    expect(method_exists(User::class, 'roles'))->toBeTrue()
        ->and((new User)->roles())->toBeInstanceOf(MorphToMany::class);
});

test('seeding twice does not duplicate anything', function () {
    seedRolesAndPermissions();
    $this->seed(PermissionSeeder::class);

    expect(Role::query()->count())->toBe(1)
        // Counted rather than hardcoded, so adding a permission does not make
        // this test look like a duplicate bug.
        ->and(Permission::query()->count())->toBe(app(PermissionSeeder::class)->expectedCount());
});

test('the super admin seeder creates an active account with the role', function () {
    seedRolesAndPermissions();

    $user = app(SuperAdminSeeder::class)->seedSuperAdmin(
        'Super Admin Test',
        'superadmin@example.test',
        'RahasiaKuat#2026',
    );

    expect($user->exists)->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and($user->hasRole('super_admin'))->toBeTrue()
        ->and(Hash::check('RahasiaKuat#2026', $user->password))->toBeTrue();
});

test('the super admin seeder is idempotent', function () {
    seedRolesAndPermissions();

    $seeder = app(SuperAdminSeeder::class);

    $seeder->seedSuperAdmin('Super Admin Test', 'superadmin@example.test', 'RahasiaKuat#2026');
    $seeder->seedSuperAdmin('Super Admin Test', 'superadmin@example.test', 'RahasiaKuat#2026');

    expect(User::query()->where('email', 'superadmin@example.test')->count())->toBe(1);
});

test('the super admin seeder rejects a weak password', function () {
    seedRolesAndPermissions();

    expect(fn () => app(SuperAdminSeeder::class)->seedSuperAdmin(
        'Super Admin Test',
        'lemah@example.test',
        '123',
    ))->toThrow(ValidationException::class);

    expect(User::query()->where('email', 'lemah@example.test')->exists())->toBeFalse();
});

test('the super admin seeder reactivates a previously deactivated account', function () {
    // Phase 01 Workstream 06 requires is_active = true on a seeded account.
    seedRolesAndPermissions();

    $existing = User::factory()->inactive()->create([
        'email' => 'superadmin@example.test',
    ]);

    $user = app(SuperAdminSeeder::class)->seedSuperAdmin(
        'Super Admin Test',
        'superadmin@example.test',
        'RahasiaKuat#2026',
    );

    expect($user->getKey())->toBe($existing->getKey())
        ->and($user->is_active)->toBeTrue();
});
