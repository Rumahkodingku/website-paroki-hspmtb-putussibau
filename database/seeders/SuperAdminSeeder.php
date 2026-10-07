<?php

namespace Database\Seeders;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the first Super Admin account from environment variables.
 *
 * Phase 01 Workstream 06 requires a fresh installation to be able to produce a
 * usable Super Admin without hard-coding anything. Credentials come from
 * ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD.
 *
 * When those are absent the seeder does nothing: a teammate who has not filled
 * in their own .env, and CI, both run `migrate:fresh --seed` without them and
 * must not fail because of it.
 *
 * @see docs/DECISIONS.md D-17
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the application's super admin account.
     */
    public function run(): void
    {
        $email = $this->configured('email');
        $password = $this->configured('password');

        if ($email === null || $password === null) {
            // Not an error: a teammate's machine and CI both run
            // `migrate:fresh --seed` without admin credentials.
            return;
        }

        $this->seedSuperAdmin($this->configured('name') ?? 'Super Admin', $email, $password);
    }

    /**
     * Create or update the Super Admin account.
     *
     * Separate from run() so tests can seed without touching the process
     * environment.
     *
     * Uses forceFill() rather than updateOrCreate() on purpose. is_active is
     * deliberately absent from $fillable (see docs/DECISIONS.md D-13), so
     * mass assignment would silently drop it and the account would end up
     * deactivated — the exact opposite of what Phase 01 Workstream 06 asks for.
     * Seeder code is trusted, so bypassing the fillable guard here is correct.
     *
     * @throws ValidationException when the supplied password is too weak
     */
    public function seedSuperAdmin(string $name, string $email, string $password): User
    {
        Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::defaults()]],
        )->validate();

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'password' => $password,
            // An account seeded for the first time must be able to log in.
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->syncRoles([AppServiceProvider::SUPER_ADMIN]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    /**
     * Read a bootstrap credential from config, treating blanks as absent.
     */
    private function configured(string $key): ?string
    {
        $value = config("admin.{$key}");

        return is_string($value) && filled($value) ? $value : null;
    }
}
