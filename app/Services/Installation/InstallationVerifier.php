<?php

namespace App\Services\Installation;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/**
 * The last gate before the wizard is allowed to say "completed".
 *
 * Each check is a fact the application will depend on afterwards, expressed as
 * a stable key rather than a message: the UI shows one safe sentence and the
 * log keeps the detail. Nothing here trusts the state that was written during
 * the run; it reads the database again.
 *
 * @see docs/DECISIONS.md D-91
 */
final class InstallationVerifier
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    /**
     * @return list<string>
     */
    public function failures(string $adminEmail): array
    {
        $failures = [];

        if (! $this->migrationsAreCurrent()) {
            $failures[] = 'migrations_pending';
        }

        if (! $this->roleExists()) {
            $failures[] = 'role_missing';
        }

        $user = User::query()->where('email', $adminEmail)->first();

        if ($user === null) {
            $failures[] = 'admin_missing';
        } else {
            if (! $user->hasRole((string) config('installer.role'))) {
                $failures[] = 'admin_role_missing';
            }

            if (! $user->is_active) {
                $failures[] = 'admin_inactive';
            }

            if ($user->email_verified_at === null) {
                $failures[] = 'admin_unverified';
            }
        }

        if (! $this->settingIsFilled('parish_name')) {
            $failures[] = 'setting_parish_name';
        }

        if (! $this->settingIsFilled('parish_short_name')) {
            $failures[] = 'setting_parish_short_name';
        }

        if (User::query()->where('email', 'redaksi-demo@example.test')->exists()) {
            $failures[] = 'demo_content_present';
        }

        if (! Route::has('login')) {
            $failures[] = 'login_route_missing';
        }

        return $failures;
    }

    private function migrationsAreCurrent(): bool
    {
        $migrator = app('migrator');

        $files = array_keys($migrator->getMigrationFiles(database_path('migrations')));
        $ran = $migrator->getRepository()->getRan();

        return array_diff($files, $ran) === [];
    }

    private function roleExists(): bool
    {
        return Role::query()
            ->where('name', (string) config('installer.role'))
            ->where('guard_name', (string) config('auth.defaults.guard'))
            ->exists();
    }

    private function settingIsFilled(string $key): bool
    {
        $value = $this->settings->get($key);

        return is_string($value) && trim($value) !== '';
    }
}
