<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    configureInstaller();

    config([
        'app.url' => 'https://paroki.example.test',
        'installer.requirements.minimum_execution_time' => 0,
    ]);

    $this->withSession([
        'installation.database_verified_at' => now()->toIso8601String(),
    ]);
});

afterEach(function () {
    resetInstallerStatusFile();
});

test('the super admin step renders', function () {
    $this->get('/install/super-admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('installation/wizard/super-admin')
            ->where('values.name', '')
            ->where('values.email', ''));
});

test('invalid input is rejected without creating a user', function () {
    $this->post('/install/super-admin/validate', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['name', 'email', 'password']);

    expect(User::query()->count())->toBe(0);
});

test('valid input is stored in the session with a normalised email', function () {
    $this->post('/install/super-admin/validate', [
        'name' => '  Administrator Paroki  ',
        'email' => '  Admin@Example.TEST ',
        'password' => 'Installation-2026!',
        'password_confirmation' => 'Installation-2026!',
    ])->assertRedirect(route('install.application'));

    expect(session('installation.super_admin'))->toMatchArray([
        'name' => 'Administrator Paroki',
        'email' => 'admin@example.test',
        'password' => 'Installation-2026!',
    ]);

    expect(User::query()->count())->toBe(0);
});

test('the password is never sent back to the browser', function () {
    $this->post('/install/super-admin/validate', [
        'name' => 'Administrator Paroki',
        'email' => 'admin@example.test',
        'password' => 'Very-Secret-2026!',
        'password_confirmation' => 'Very-Secret-2026!',
    ]);

    $this->get('/install/application')
        ->assertOk()
        ->assertDontSee('Very-Secret-2026!');
});

test('the application step requires a stored super admin', function () {
    $this->withSession([]);

    $this->get('/install/application')->assertRedirect(route('install.super-admin'));
});
