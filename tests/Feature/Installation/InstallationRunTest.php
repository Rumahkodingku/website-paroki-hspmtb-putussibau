<?php

use App\Models\CommunityCategory;
use App\Models\DocumentCategory;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SiteSetting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\Installation\DatabaseReadinessChecker;
use App\Services\Installation\EnvironmentWriter;
use App\Services\Installation\InstallationState;
use App\Services\SiteSettingsService;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fixtures\Installation\ThrowingSeeder;

beforeEach(function () {
    configureInstaller();

    config([
        'app.url' => 'https://paroki.example.test',
        'installer.requirements.minimum_execution_time' => 0,
    ]);

    app()->instance(DatabaseReadinessChecker::class, fakeDatabaseReadiness());

    app()->instance(EnvironmentWriter::class, new class extends EnvironmentWriter
    {
        public function write(array $values): bool
        {
            return true;
        }
    });

    $this->withSession(installationTestSession());
});

afterEach(function () {
    resetInstallerStatusFile();
});

test('a fresh database is installed, seeded, verified, and locked', function () {
    $this->postJson('/install/run', ['confirmed' => true])
        ->assertOk()
        ->assertJson(['status' => 'completed']);

    expect(app(InstallationState::class)->status())
        ->toBe(InstallationState::COMPLETED);

    $admin = User::query()->where('email', 'installer-admin@example.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and($admin->hasRole(AppServiceProvider::SUPER_ADMIN))->toBeTrue();

    expect(app(SiteSettingsService::class)->get('parish_name'))->toBe('Paroki Uji')
        ->and(app(SiteSettingsService::class)->get('parish_short_name'))->toBe('PU');

    expect(SiteSetting::query()
        ->whereIn('key', ['parish_name', 'parish_short_name', 'tagline', 'short_description', 'patron_saint'])
        ->count())->toBe(5);

    expect(PostCategory::query()->count())->toBe(4)
        ->and(EventCategory::query()->count())->toBe(5)
        ->and(ServiceCategory::query()->count())->toBe(3)
        ->and(DocumentCategory::query()->count())->toBe(5)
        ->and(CommunityCategory::query()->count())->toBe(3);

    expect(Post::query()->count())->toBe(0)
        ->and(Event::query()->count())->toBe(0)
        ->and(Service::query()->count())->toBe(0)
        ->and(User::query()->where('email', 'redaksi-demo@example.test')->exists())->toBeFalse();

    expect(CommunityCategory::query()->where('slug', 'organisasi-kategorial')->value('description'))
        ->toBeNull();
});

test('the status file holds no credentials', function () {
    $this->postJson('/install/run', ['confirmed' => true])->assertOk();

    $contents = (string) file_get_contents(installerStatusFile());

    expect($contents)
        ->not->toContain('Installer-2026!')
        ->not->toContain('installer-admin@example.test');
});

test('the success page is refresh-safe inside the session and closed to a new one', function () {
    $this->postJson('/install/run', ['confirmed' => true])->assertOk();

    $this->get('/install/complete')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('installation/wizard/complete')
            ->where('admin.email', 'installer-admin@example.test'));

    $this->get('/install/complete')->assertOk();

    $this->flushSession();

    $this->get('/install/complete')->assertRedirect(route('login'));
});

test('the created super admin can log in', function () {
    $this->postJson('/install/run', ['confirmed' => true])->assertOk();

    $this->post('/admin/login', [
        'email' => 'installer-admin@example.test',
        'password' => 'Installer-2026!',
    ])->assertRedirect('/admin');

    $this->assertAuthenticated();
});

test('a second run is refused after completion', function () {
    $this->postJson('/install/run', ['confirmed' => true])->assertOk();

    $this->postJson('/install/run', ['confirmed' => true])
        ->assertStatus(409)
        ->assertJson(['status' => 'completed']);

    expect(User::query()->where('email', 'installer-admin@example.test')->count())->toBe(1);
});

test('a failed verification keeps the installation unfinished', function () {
    $this->withSession([
        'installation.application' => [
            'parish_name' => '',
            'parish_short_name' => '',
            'tagline' => null,
            'short_description' => null,
            'patron_saint' => null,
        ],
    ]);

    $this->postJson('/install/run', ['confirmed' => true])
        ->assertOk()
        ->assertJson([
            'status' => 'failed',
            'error_code' => 'VERIFICATION_FAILED',
        ]);

    expect(app(InstallationState::class)->status())->toBe(InstallationState::FAILED)
        ->and(app(InstallationState::class)->errorCode())->toBe('VERIFICATION_FAILED');
});

test('an existing email is never overwritten', function () {
    User::factory()->create(['email' => 'installer-admin@example.test']);

    $this->postJson('/install/run', ['confirmed' => true])
        ->assertOk()
        ->assertJson([
            'status' => 'failed',
            'error_code' => 'ADMIN_EMAIL_TAKEN',
        ]);

    expect(User::query()->where('email', 'installer-admin@example.test')->count())->toBe(1);
});

test('a failing production seeder stops the installation', function () {
    config(['installer.seeders.permissions' => [ThrowingSeeder::class]]);

    $this->postJson('/install/run', ['confirmed' => true])
        ->assertOk()
        ->assertJson([
            'status' => 'failed',
            'error_code' => 'SEEDER_FAILED',
        ]);

    expect(User::query()->where('email', 'installer-admin@example.test')->exists())->toBeFalse();
});

test('the run requires an explicit confirmation', function () {
    $this->postJson('/install/run', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('confirmed');

    expect(app(InstallationState::class)->status())->toBe(InstallationState::PENDING);
});

test('the run requires the session data collected by the previous steps', function () {
    $this->flushSession();

    $this->postJson('/install/run', ['confirmed' => true])
        ->assertStatus(409)
        ->assertJson(['error_code' => 'SESSION_EXPIRED']);

    expect(app(InstallationState::class)->status())->toBe(InstallationState::PENDING);
});
