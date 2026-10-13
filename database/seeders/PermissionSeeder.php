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
 * Permissions for the other modules (agenda, gallery, communities, mass
 * schedules, and so on) are deliberately absent. Each module will need a
 * different set, so they are added when the module is built rather than
 * creating permissions nothing reads yet.
 *
 * The media trio is the exception that proves the rule: P10 added
 * media.view, media.create and media.delete, and they are read immediately by
 * the upload endpoints. Nothing exists here for a module that has no code to
 * guard yet.
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

        /*
         * Berita & Agenda, added with the module in Phase 05.
         *
         * `posts.*` was already here from Phase 01 with nothing reading it; the
         * policy that reads it arrives with PostPolicy. The three sets below are
         * the ones this module adds.
         *
         * Categories have their own abilities rather than riding on
         * `posts.*`. Deleting a category is a different act from deleting an
         * article — it is refused outright while anything uses it — and the day
         * an editor role exists it is the first distinction they will want.
         *
         * Single words for the reason D-36 gives: RbacFoundationTest asserts
         * every permission matches /^[a-z]+\.[a-z]+$/, and `postcategories.view`
         * satisfies it where `post-categories.view` would not.
         */
        'postcategories.view',
        'postcategories.create',
        'postcategories.update',
        'postcategories.delete',

        'events.view',
        'events.create',
        'events.update',
        'events.delete',

        'eventcategories.view',
        'eventcategories.create',
        'eventcategories.update',
        'eventcategories.delete',

        /*
         * Jadwal Misa, added with the module in Phase 04.
         *
         * Three abilities per resource rather than one `mass-schedule.*` set,
         * because D-18 and D-24 both argue that a permission nothing reads is
         * worse than no permission at all: a named resource is what a future
         * editor role could be granted one module at a time.
         *
         * All single words, deliberately. RbacFoundationTest asserts every
         * permission matches /^[a-z]+\.[a-z]+$/, and `mass-schedules.view` would
         * not. Relaxing that test to allow hyphens would be a small thing to do
         * on purpose; doing it by accident while adding a module is not one.
         * See D-36.
         *
         *
         * The delete abilities came with the soft-delete endpoints added in
         * D-40, which also closed the ADM-05 divergence D-34 section 1 had
         * accepted. They are what makes deletion a super-admin-only action
         * without hard-coding a role check into four policies: super_admin is
         * the only role on the MVP, so today the two are the same, and the day a
         * second role exists this is the line to move. See D-36.
         */
        'locations.view',
        'locations.create',
        'locations.update',
        'locations.delete',

        'massschedules.view',
        'massschedules.create',
        'massschedules.update',
        'massschedules.delete',

        'specialmasses.view',
        'specialmasses.create',
        'specialmasses.update',
        'specialmasses.delete',

        'schedulenotices.view',
        'schedulenotices.create',
        'schedulenotices.update',
        'schedulenotices.delete',

        /*
         * Profil Paroki, added with the module in Phase 6A (D-52).
         *
         * The profile and the service-area narrative are singletons, so they
         * have only view/update — there is no create or delete endpoint for a
         * row that must always be exactly one. The service area therefore reads
         * parishprofiles.view / parishprofiles.update rather than growing a
         * sixth group.
         *
         * History, pastors (with their assignments), board periods and board
         * members are ordinary CRUD and carry all four verbs.
         *
         * All single words, per D-36: RbacFoundationTest asserts every
         * permission matches /^[a-z]+\.[a-z]+$/.
         */
        'parishprofiles.view',
        'parishprofiles.update',

        'parishhistories.view',
        'parishhistories.create',
        'parishhistories.update',
        'parishhistories.delete',

        'pastors.view',
        'pastors.create',
        'pastors.update',
        'pastors.delete',

        'boardperiods.view',
        'boardperiods.create',
        'boardperiods.update',
        'boardperiods.delete',

        'boardmembers.view',
        'boardmembers.create',
        'boardmembers.update',
        'boardmembers.delete',
    ];

    /**
     * Seed the system role and the application's permissions.
     */
    /**
     * How many permissions this seeder defines.
     *
     * Exposed so tests can assert idempotency without hardcoding a number that
     * has to be edited every time a module adds one.
     */
    public function expectedCount(): int
    {
        return count(self::PERMISSIONS);
    }

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
