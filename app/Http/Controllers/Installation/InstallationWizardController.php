<?php

namespace App\Http\Controllers\Installation;

use App\Enums\InstallationError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Installation\StoreApplicationConfigurationRequest;
use App\Http\Requests\Installation\StoreEmailConfigurationRequest;
use App\Http\Requests\Installation\StoreSuperAdminSetupRequest;
use App\Http\Requests\Installation\TestDatabaseConnectionRequest;
use App\Http\Requests\Installation\TestEmailConnectionRequest;
use App\Services\Installation\DatabaseConnectionTester;
use App\Services\Installation\DatabaseReadinessChecker;
use App\Services\Installation\InstallationState;
use App\Services\Installation\MailConnectionTester;
use App\Services\Installation\SystemRequirementsChecker;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class InstallationWizardController extends Controller
{
    private const STEPS = [
        ['key' => 'welcome', 'label' => 'Selamat datang'],
        ['key' => 'requirements', 'label' => 'Persyaratan sistem'],
        ['key' => 'database', 'label' => 'Database'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'super-admin', 'label' => 'Akun Super Admin'],
        ['key' => 'application', 'label' => 'Identitas paroki'],
        ['key' => 'install', 'label' => 'Instalasi'],
        ['key' => 'complete', 'label' => 'Selesai'],
    ];

    private const APPLICATION_KEYS = [
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
        private readonly DatabaseConnectionTester $connection,
        private readonly DatabaseReadinessChecker $readiness,
        private readonly SiteSettingsService $settings,
        private readonly MailConnectionTester $mailTester,
    ) {}

    public function show(): Response|RedirectResponse
    {
        return match ($this->state->status()) {
            InstallationState::INSTALLING, InstallationState::FAILED => redirect()->route('install.review'),
            default => Inertia::render('installation/wizard', [
                'steps' => self::STEPS,
                'currentStep' => 0,
            ]),
        };
    }

    public function requirements(): Response|RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        return Inertia::render('installation/wizard/requirements', [
            'steps' => self::STEPS,
            'currentStep' => 1,
            'checks' => $this->requirements->checks(),
            'passed' => $this->requirements->passes(),
        ]);
    }

    public function checkRequirements(): RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        return back();
    }

    public function database(): Response|RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        if (! $this->requirements->passes()) {
            return redirect()->route('install.requirements');
        }

        return Inertia::render('installation/wizard/database', [
            'steps' => self::STEPS,
            'currentStep' => 2,
            'database' => $this->databaseInfoForForm(),
            'supportedDrivers' => (array) config('installer.database.supported_drivers', []),
            'verifiedAt' => session('installation.database_verified_at'),
        ]);
    }

    public function testDatabase(TestDatabaseConnectionRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        $data = $request->validated();

        $connectionConfig = $this->connectionConfigFromInput($data);

        $connection = $this->connection->test($connectionConfig);

        if (! $connection['ok']) {
            return back()->withErrors(['database' => $connection['message']])->withInput();
        }

        $readiness = $this->readiness->check($connectionConfig);

        if (! $readiness['ok']) {
            return back()->withErrors(['database' => $readiness['message']])->withInput();
        }

        session(['installation.database' => [
            'driver' => $data['driver'],
            'host' => $data['host'],
            'port' => $data['port'],
            'database' => $data['database'],
            'username' => $data['username'],
            'password' => $data['password'] ?? null,
        ]]);

        session(['installation.database_verified_at' => now()->toIso8601String()]);

        return redirect()->route('install.email');
    }

    public function email(): Response|RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        if (! session()->has('installation.database_verified_at')) {
            return redirect()->route('install.database');
        }

        $values = session('installation.email', []);

        return Inertia::render('installation/wizard/email', [
            'steps' => self::STEPS,
            'currentStep' => 3,
            'values' => $this->emailValues($values),
        ]);
    }

    public function storeEmail(StoreEmailConfigurationRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        if (! session()->has('installation.database_verified_at')) {
            return redirect()->route('install.database');
        }

        session(['installation.email' => $request->validated()]);

        return redirect()->route('install.super-admin');
    }

    public function testEmail(TestEmailConnectionRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        if (! session()->has('installation.database_verified_at')) {
            return redirect()->route('install.database');
        }

        $data = $request->validated();

        $result = $this->mailTester->test($data);

        if (! $result['ok']) {
            return back()->withErrors(['email' => $result['message']])->withInput();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $result['message']]);

        return back();
    }

    public function superAdmin(): Response|RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        if (! session()->has('installation.database_verified_at')) {
            return redirect()->route('install.database');
        }

        if (! session()->has('installation.email')) {
            return redirect()->route('install.email');
        }

        $values = session('installation.super_admin', []);

        return Inertia::render('installation/wizard/super-admin', [
            'steps' => self::STEPS,
            'currentStep' => 4,
            'values' => [
                'name' => is_string($values['name'] ?? null) ? $values['name'] : '',
                'email' => is_string($values['email'] ?? null) ? $values['email'] : '',
            ],
        ]);
    }

    public function storeSuperAdmin(StoreSuperAdminSetupRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        if (! session()->has('installation.database_verified_at')) {
            return redirect()->route('install.database');
        }

        if (! session()->has('installation.email')) {
            return redirect()->route('install.email');
        }

        $data = $request->validated();

        session(['installation.super_admin' => [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]]);

        return redirect()->route('install.application');
    }

    public function application(): Response|RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        if (! session()->has('installation.super_admin')) {
            return redirect()->route('install.super-admin');
        }

        $values = session('installation.application', []);

        return Inertia::render('installation/wizard/application', [
            'steps' => self::STEPS,
            'currentStep' => 5,
            'values' => $this->applicationValues($values),
            'meta' => $this->metaForForm($values),
        ]);
    }

    public function storeApplication(StoreApplicationConfigurationRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectUnlessPending()) {
            return $redirect;
        }

        if (! session()->has('installation.super_admin')) {
            return redirect()->route('install.super-admin');
        }

        session(['installation.application' => $request->validated()]);

        return redirect()->route('install.review');
    }

    public function review(): Response|RedirectResponse
    {
        $status = $this->state->status();

        if ($status === InstallationState::INSTALLING || $status === InstallationState::FAILED) {
            return Inertia::render('installation/wizard/review', [
                'steps' => self::STEPS,
                'currentStep' => 6,
                'status' => $status,
                'progress' => $this->progressPayload(),
                'error' => $this->errorPayload(),
                'summary' => null,
            ]);
        }

        if ($status === InstallationState::COMPLETED) {
            return redirect()->route('login');
        }

        if (! session()->has('installation.database_verified_at')) {
            return redirect()->route('install.database');
        }

        if (! session()->has('installation.email')) {
            return redirect()->route('install.email');
        }

        if (! session()->has('installation.super_admin')) {
            return redirect()->route('install.super-admin');
        }

        if (! session()->has('installation.application')) {
            return redirect()->route('install.application');
        }

        $admin = session('installation.super_admin');
        $application = session('installation.application');
        $email = session('installation.email');

        return Inertia::render('installation/wizard/review', [
            'steps' => self::STEPS,
            'currentStep' => 6,
            'status' => InstallationState::PENDING,
            'progress' => $this->progressPayload(),
            'error' => null,
            'summary' => [
                'database' => $this->databaseInfoForReview(),
                'email' => $this->emailValues(is_array($email) ? $email : []),
                'admin' => [
                    'name' => is_string($admin['name'] ?? null) ? $admin['name'] : '',
                    'email' => is_string($admin['email'] ?? null) ? $admin['email'] : '',
                ],
                'application' => $this->applicationValues($application),
                'meta' => $this->metaForForm($application),
            ],
        ]);
    }

    public function complete(): Response|RedirectResponse
    {
        if (! $this->state->isCompleted()) {
            return redirect()->route('install.review');
        }

        if (! session()->has('installation.completed_notice')) {
            return redirect()->route('login');
        }

        $admin = session('installation.completed_summary', []);

        return Inertia::render('installation/wizard/complete', [
            'steps' => self::STEPS,
            'currentStep' => 7,
            'siteName' => $this->siteName(),
            'admin' => [
                'name' => is_string($admin['name'] ?? null) ? $admin['name'] : '',
                'email' => is_string($admin['email'] ?? null) ? $admin['email'] : '',
            ],
            'links' => [
                'home' => route('home'),
                'login' => route('login'),
            ],
        ]);
    }

    private function redirectUnlessPending(): ?RedirectResponse
    {
        return match ($this->state->status()) {
            InstallationState::PENDING => null,
            InstallationState::INSTALLING, InstallationState::FAILED => redirect()->route('install.review'),
            default => redirect()->route('login'),
        };
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string|null>
     */
    private function applicationValues(array $values): array
    {
        $result = [];

        foreach (self::APPLICATION_KEYS as $key) {
            $value = $values[$key] ?? null;
            $result[$key] = is_string($value) && trim($value) !== '' ? $value : null;
        }

        return $result;
    }

    /**
     * @return array{driver: string, host: string|null, port: int|null, database: string|null, username: string|null, supported: bool}
     */
    private function databaseInfo(): array
    {
        $driver = (string) config('database.default');
        $config = (array) config("database.connections.{$driver}", []);

        return [
            'driver' => $driver,
            'host' => is_string($config['host'] ?? null) ? $config['host'] : null,
            'port' => is_int($config['port'] ?? null) ? $config['port'] : null,
            'database' => is_string($config['database'] ?? null) ? $config['database'] : null,
            'username' => is_string($config['username'] ?? null) ? $config['username'] : null,
            'supported' => in_array($driver, (array) config('installer.database.supported_drivers'), true),
        ];
    }

    /**
     * @return array{driver: string, host: string|null, port: int|null, database: string|null, username: string|null, supported: bool}
     */
    private function databaseInfoForForm(): array
    {
        $session = session('installation.database');

        if (is_array($session)) {
            return [
                'driver' => is_string($session['driver'] ?? null) ? $session['driver'] : (string) config('database.default'),
                'host' => is_string($session['host'] ?? null) ? $session['host'] : null,
                'port' => is_numeric($session['port'] ?? null) ? (int) $session['port'] : null,
                'database' => is_string($session['database'] ?? null) ? $session['database'] : null,
                'username' => is_string($session['username'] ?? null) ? $session['username'] : null,
                'supported' => true,
            ];
        }

        return $this->databaseInfo();
    }

    /**
     * @return array{driver: string, host: string|null, port: int|null, database: string|null, username: string|null, supported: bool}
     */
    private function databaseInfoForReview(): array
    {
        $session = session('installation.database');

        if (is_array($session)) {
            return [
                'driver' => is_string($session['driver'] ?? null) ? $session['driver'] : (string) config('database.default'),
                'host' => is_string($session['host'] ?? null) ? $session['host'] : null,
                'port' => is_numeric($session['port'] ?? null) ? (int) $session['port'] : null,
                'database' => is_string($session['database'] ?? null) ? $session['database'] : null,
                'username' => is_string($session['username'] ?? null) ? $session['username'] : null,
                'supported' => true,
            ];
        }

        return $this->databaseInfo();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function connectionConfigFromInput(array $input): array
    {
        return [
            'driver' => (string) $input['driver'],
            'host' => (string) $input['host'],
            'port' => (int) $input['port'],
            'database' => (string) $input['database'],
            'username' => (string) $input['username'],
            'password' => $input['password'] ?? null,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{mailer: string, host: string|null, port: int|null, username: string|null, from_address: string|null, from_name: string|null}
     */
    private function emailValues(array $values): array
    {
        return [
            'mailer' => is_string($values['mailer'] ?? null) && trim($values['mailer']) !== ''
                ? $values['mailer']
                : 'smtp',
            'host' => is_string($values['host'] ?? null) && trim($values['host']) !== ''
                ? $values['host']
                : '127.0.0.1',
            'port' => is_numeric($values['port'] ?? null) ? (int) $values['port'] : 1025,
            'username' => is_string($values['username'] ?? null) && trim($values['username']) !== ''
                ? $values['username']
                : null,
            'password' => is_string($values['password'] ?? null) && trim($values['password']) !== ''
                ? $values['password']
                : null,
            'from_address' => is_string($values['from_address'] ?? null) && trim($values['from_address']) !== ''
                ? $values['from_address']
                : 'noreply@paroki-hspmtb.local',
            'from_name' => is_string($values['from_name'] ?? null) && trim($values['from_name']) !== ''
                ? $values['from_name']
                : (string) config('app.name'),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{appName: string, appUrl: string, timezone: string, locale: string, environment: string}
     */
    private function metaForForm(array $values): array
    {
        return [
            'appName' => is_string($values['app_name'] ?? null) && trim($values['app_name']) !== ''
                ? $values['app_name']
                : (string) config('app.name'),
            'appUrl' => is_string($values['app_url'] ?? null) && trim($values['app_url']) !== ''
                ? $values['app_url']
                : (string) config('app.url'),
            'timezone' => is_string($values['timezone'] ?? null) && trim($values['timezone']) !== ''
                ? $values['timezone']
                : (string) config('app.display_timezone'),
            'locale' => is_string($values['locale'] ?? null) && trim($values['locale']) !== ''
                ? $values['locale']
                : (string) config('app.locale'),
            'environment' => is_string($values['environment'] ?? null) && trim($values['environment']) !== ''
                ? $values['environment']
                : 'production',
        ];
    }

    /**
     * @return array{status: string, current_step: string|null, started_at: string|null, completed_at: string|null, failed_at: string|null, error_code: string|null}
     */
    private function progressPayload(): array
    {
        $payload = $this->state->payload();

        return [
            'status' => $payload['status'],
            'current_step' => $payload['current_step'],
            'started_at' => $payload['started_at'],
            'completed_at' => $payload['completed_at'],
            'failed_at' => $payload['failed_at'],
            'error_code' => $payload['error_code'],
        ];
    }

    /**
     * @return array{code: string, message: string}|null
     */
    private function errorPayload(): ?array
    {
        $code = $this->state->errorCode();

        if ($code === null) {
            return null;
        }

        $error = InstallationError::tryFrom($code);

        return [
            'code' => $code,
            'message' => $error?->message() ?? InstallationError::InstallFailed->message(),
        ];
    }

    private function siteName(): string
    {
        $name = $this->settings->get('parish_name');

        return is_string($name) && trim($name) !== ''
            ? $name
            : (string) config('app.name');
    }
}
