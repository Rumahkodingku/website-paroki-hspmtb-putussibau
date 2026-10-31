<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    configureInstaller();

    config([
        'app.url' => 'https://paroki.example.test',
        'installer.requirements.minimum_execution_time' => 0,
    ]);

    $this->withSession([
        'installation.database_verified_at' => now()->toIso8601String(),
        'installation.super_admin' => [
            'name' => 'Administrator Paroki',
            'email' => 'admin@example.test',
            'password' => 'Installation-2026!',
        ],
    ]);
});

afterEach(function () {
    resetInstallerStatusFile();
});

test('the application step renders the identity fields and read-only meta', function () {
    $this->get('/install/application')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('installation/wizard/application')
            ->where('values.parish_name', null)
            ->where('values.parish_short_name', null)
            ->where('meta.timezone', config('app.display_timezone'))
            ->missing('values.password'));
});

test('optional blanks are stored as null and unknown keys are dropped', function () {
    $this->post('/install/application/validate', [
        'parish_name' => '  Paroki Uji  ',
        'parish_short_name' => '  PU  ',
        'tagline' => '   ',
        'short_description' => '',
        'motto_verse' => '   ',
        'patron_saint' => null,
        'app_name' => '  Aplikasi Uji  ',
        'app_url' => '  https://uji.example.test  ',
        'timezone' => '  Asia/Pontianak  ',
        'locale' => '  id  ',
        'environment' => '  production  ',
        'contact_phone' => '08123456789',
    ])->assertRedirect(route('install.review'));

    expect(session('installation.application'))->toBe([
        'parish_name' => 'Paroki Uji',
        'parish_short_name' => 'PU',
        'tagline' => null,
        'short_description' => null,
        'motto_verse' => null,
        'patron_saint' => null,
        'app_name' => 'Aplikasi Uji',
        'app_url' => 'https://uji.example.test',
        'timezone' => 'Asia/Pontianak',
        'locale' => 'id',
        'environment' => 'production',
    ]);
});

test('the identity fields are required', function () {
    $this->post('/install/application/validate', [
        'parish_name' => '',
        'parish_short_name' => '',
    ])->assertSessionHasErrors(['parish_name', 'parish_short_name']);
});
