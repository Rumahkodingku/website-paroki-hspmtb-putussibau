<?php

namespace App\Services\Installation;

use App\Enums\InstallationError;
use App\Models\User;
use App\Services\SiteSettingsService;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class InstallationRunner
{
    private const SETTING_KEYS = [
        'parish_name',
        'parish_short_name',
        'tagline',
        'short_description',
        'motto_verse',
        'patron_saint',
    ];

    public function __construct(
        private readonly InstallationState $state,
        private readonly SystemRequirementsChecker $requirements,
        private readonly DatabaseReadinessChecker $readiness,
        private readonly ProductionSeederRunner $seeders,
        private readonly InstallationVerifier $verifier,
        private readonly SiteSettingsService $settings,
        private readonly EnvironmentWriter $environment,
    ) {}

    /**
     * @param  array<string, mixed>  $admin
     * @param  array<string, mixed>  $application
     * @param  array<string, mixed>|null  $database
     * @return array{status: string, error_code: string|null}
     */
    public function run(array $admin, array $application, ?array $database = null): array
    {
        $lock = Cache::lock(
            (string) config('installer.lock_key'),
            (int) config('installer.lock_seconds'),
        );

        if (! $lock->get()) {
            return $this->failed(InstallationError::InstallLocked);
        }

        $step = 'validate';

        try {
            if ($this->state->status() !== InstallationState::PENDING) {
                return $this->failed(InstallationError::InstallLocked);
            }

            @set_time_limit(300);

            if (is_array($database)) {
                $this->applyDatabaseConfig($database);
            }

            $this->state->begin();

            $this->state->step($step);

            if (! $this->requirements->passes()) {
                throw new InstallationFailure(InstallationError::RequirementsFailed);
            }

            $readiness = $this->readiness->check(
                is_array($database) ? $this->connectionConfigFromInput($database) : null,
            );

            if (! $readiness['ok']) {
                throw new InstallationFailure(
                    InstallationError::tryFrom((string) $readiness['code']) ?? InstallationError::DatabaseUnreachable,
                );
            }

            $step = 'migrate';
            $this->state->step($step);

            if (Artisan::call('migrate', ['--force' => true]) !== 0) {
                throw new InstallationFailure(InstallationError::MigrationFailed);
            }

            $step = 'seed_permissions';
            $this->state->step($step);
            $this->seeders->runPermissions();

            $step = 'seed_categories';
            $this->state->step($step);
            $this->seeders->runCategories();

            $step = 'create_admin';
            $this->state->step($step);

            $email = (string) ($admin['email'] ?? '');

            if (User::query()->where('email', $email)->exists()) {
                throw new InstallationFailure(InstallationError::AdminEmailTaken);
            }

            resolve(SuperAdminSeeder::class)->seedSuperAdmin(
                (string) ($admin['name'] ?? ''),
                $email,
                (string) ($admin['password'] ?? ''),
            );

            $step = 'save_settings';
            $this->state->step($step);
            $this->settings->setMany(array_intersect_key($application, array_flip(self::SETTING_KEYS)));

            $step = 'verify';
            $this->state->step($step);

            if ($this->verifier->failures($email) !== []) {
                throw new InstallationFailure(InstallationError::VerificationFailed);
            }

            $step = 'save_environment';
            $this->state->step($step);

            $envValues = [
                'DB_CONNECTION' => is_array($database) ? (string) ($database['driver'] ?? config('database.default')) : (string) config('database.default'),
            ];

            if (is_array($database)) {
                $envValues['DB_HOST'] = (string) ($database['host'] ?? '');
                $envValues['DB_PORT'] = (string) ($database['port'] ?? '');
                $envValues['DB_DATABASE'] = (string) ($database['database'] ?? '');
                $envValues['DB_USERNAME'] = (string) ($database['username'] ?? '');
                $envValues['DB_PASSWORD'] = (string) ($database['password'] ?? '');
            }

            $envValues['APP_NAME'] = (string) ($application['app_name'] ?? config('app.name'));
            $envValues['APP_URL'] = (string) ($application['app_url'] ?? config('app.url'));
            $envValues['APP_DISPLAY_TIMEZONE'] = (string) ($application['timezone'] ?? config('app.display_timezone'));
            $envValues['APP_LOCALE'] = (string) ($application['locale'] ?? config('app.locale'));
            $envValues['APP_ENV'] = (string) ($application['environment'] ?? 'production');
            $envValues['APP_DEBUG'] = 'false';
            $envValues['TELESCOPE_ENABLED'] = 'false';

            $email = session('installation.email');

            if (is_array($email)) {
                $envValues['MAIL_MAILER'] = (string) Arr::get($email, 'mailer', 'smtp');
                $envValues['MAIL_HOST'] = (string) Arr::get($email, 'host', '127.0.0.1');
                $envValues['MAIL_PORT'] = (string) Arr::get($email, 'port', 1025);
                $envValues['MAIL_USERNAME'] = (string) (Arr::get($email, 'username') ?? '');
                $envValues['MAIL_PASSWORD'] = (string) (Arr::get($email, 'password') ?? '');
                $envValues['MAIL_FROM_ADDRESS'] = (string) Arr::get($email, 'from_address', 'noreply@paroki-hspmtb.local');
                $envValues['MAIL_FROM_NAME'] = (string) Arr::get($email, 'from_name', config('app.name'));
            }

            if (! $this->environment->write($envValues)) {
                throw new InstallationFailure(InstallationError::EnvironmentWriteFailed);
            }

            $this->state->step('lock');
            $this->state->complete();

            return ['status' => InstallationState::COMPLETED, 'error_code' => null];
        } catch (Throwable $exception) {
            $error = $exception instanceof InstallationFailure
                ? $exception->error
                : $this->errorForStep($step);

            if ($exception instanceof InstallationFailure) {
                Log::warning('installation.failed', [
                    'step' => $step,
                    'error' => $error->value,
                ]);
            } else {
                report($exception);
            }

            $this->state->fail($error);

            return $this->failed($error);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, mixed>  $database
     */
    private function applyDatabaseConfig(array $database): void
    {
        $config = $this->connectionConfigFromInput($database);
        $driver = (string) ($config['driver'] ?? config('database.default'));

        config(['database.default' => $driver]);
        config(["database.connections.{$driver}" => $config]);

        if (! app()->environment('testing')) {
            DB::purge($driver);
        }
    }

    /**
     * @param  array<string, mixed>  $database
     * @return array<string, mixed>
     */
    private function connectionConfigFromInput(array $database): array
    {
        return [
            'driver' => (string) ($database['driver'] ?? 'mysql'),
            'host' => (string) ($database['host'] ?? ''),
            'port' => (int) ($database['port'] ?? 3306),
            'database' => (string) ($database['database'] ?? ''),
            'username' => (string) ($database['username'] ?? ''),
            'password' => $database['password'] ?? null,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];
    }

    /**
     * @return array{status: string, error_code: string|null}
     */
    private function failed(InstallationError $error): array
    {
        return [
            'status' => InstallationState::FAILED,
            'error_code' => $error->value,
        ];
    }

    private function errorForStep(string $step): InstallationError
    {
        return match ($step) {
            'migrate' => InstallationError::MigrationFailed,
            'seed_permissions', 'seed_categories' => InstallationError::SeederFailed,
            'create_admin' => InstallationError::AdminCreateFailed,
            'save_settings' => InstallationError::SettingsFailed,
            'save_environment' => InstallationError::EnvironmentWriteFailed,
            'verify' => InstallationError::VerificationFailed,
            default => InstallationError::InstallFailed,
        };
    }
}
