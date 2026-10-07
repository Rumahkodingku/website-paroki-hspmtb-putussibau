<?php

namespace Database\Seeders;

use App\Providers\AppServiceProvider;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the RBAC foundation: the system role and the Phase 01 permissions.
 *
 * Phase 01 10.3 makes super_admin a system role, so it is created here and not
 * in SuperAdminSeeder: it must exist even on a machine where ADMIN_EMAIL is
 * empty and no account was seeded.
 *
 * The permission list is taken verbatim from Phase 01 10.4, plus one addition:
 * admin.access. That gate is what middleware checks to decide whether a
 * request may enter /admin/* at all (Phase 01 13). Without it the only thing
 * available to gate on would be the role name, which AUTH-R3 rules out.
 * See docs/DECISIONS.md D-18 and D-19.
 *
 * Permissions for the other modules (agenda, gallery, communities, mass * schedules, and so on) are deliberately absent. Each module will need a
 * different set, so they are added when the module is built rather than
 * creating permissions nothing reads yet.
 *
 * @see docs/DECISIONS.md D-17, D-18
 */
class PermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const PERMISSIONS = [
        'admin.access',

        'dashboard.view',

        'settings.view',
        'settings.update',

        'users.view',
        'users.create',
        'users.update',
        'users.disable',

        'posts.view',
        'posts.create',
        'posts.update',
        'posts.delete',
    ];

    /**
     * Seed the system role and the application's permissions.
     */
    public function run(): void
    {
        Role::findOrCreate(AppServiceProvider::SUPER_ADMIN, $this->guard());

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, $this->guard());
        }

        // Spatie caches the permission set, so the cache must be dropped after
        // seeding or the Gate keeps serving the pre-seed state.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Guard used for every permission and role in this application.
     *
     * Phase 01 10.5 requires the Spatie guard to match the authentication
     * guard. v8 has no `permission.guards` config to get wrong: roles inherit
     * the guard from the model, which follows config('auth.defaults.guard') —
     * 'web' here. Spelling it out keeps the intent obvious and is asserted by
     * a test.
     */
    private function guard(): string
    {
        return (string) config('auth.defaults.guard');
    }
}
