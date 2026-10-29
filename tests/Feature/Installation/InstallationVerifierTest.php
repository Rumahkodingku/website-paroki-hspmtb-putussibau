<?php

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\Installation\InstallationVerifier;
use App\Services\SiteSettingsService;
use Spatie\Permission\PermissionRegistrar;

function installerAdmin(string $email = 'admin-installer@example.test'): User
{
    $admin = User::factory()->create(['email' => $email]);

    $admin->assignRole(AppServiceProvider::SUPER_ADMIN);

    $admin->forceFill([
        'is_active' => true,
        'email_verified_at' => now(),
    ])->save();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $admin->fresh();
}

function installIdentity(): void
{
    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Paroki Pengujian',
        'parish_short_name' => 'PP',
    ]);
}

test('a complete installation has no verification failures', function () {
    seedRolesAndPermissions();
    installerAdmin();
    installIdentity();

    expect(app(InstallationVerifier::class)->failures('admin-installer@example.test'))
        ->toBe([]);
});

test('a missing role and missing admin role are reported', function () {
    installIdentity();
    $user = User::factory()->create(['email' => 'plain@example.test']);
    $user->forceFill(['is_active' => true, 'email_verified_at' => now()])->save();

    $failures = app(InstallationVerifier::class)->failures('plain@example.test');

    expect($failures)->toContain('role_missing')
        ->and($failures)->toContain('admin_role_missing');
});

test('missing identity settings are reported', function () {
    seedRolesAndPermissions();
    installerAdmin();

    $failures = app(InstallationVerifier::class)->failures('admin-installer@example.test');

    expect($failures)->toContain('setting_parish_name')
        ->and($failures)->toContain('setting_parish_short_name');
});

test('an inactive or unverified admin is reported', function () {
    seedRolesAndPermissions();
    installIdentity();

    $admin = installerAdmin('inactive@example.test');
    $admin->forceFill([
        'is_active' => false,
        'email_verified_at' => null,
    ])->save();

    $failures = app(InstallationVerifier::class)->failures('inactive@example.test');

    expect($failures)->toContain('admin_inactive')
        ->and($failures)->toContain('admin_unverified');
});

test('demo content is reported', function () {
    seedRolesAndPermissions();
    installerAdmin();
    installIdentity();

    User::factory()->create(['email' => 'redaksi-demo@example.test']);

    expect(app(InstallationVerifier::class)->failures('admin-installer@example.test'))
        ->toContain('demo_content_present');
});
