<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    configureInstaller();
});

afterEach(function () {
    resetInstallerStatusFile();
});

test('the installer is hidden when disabled', function () {
    config(['installer.enabled' => false]);

    $this->get('/install')->assertNotFound();
    $this->post('/install/run', ['confirmed' => true])->assertNotFound();
});

test('the welcome step renders on a fresh installation', function () {
    $this->get('/install')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('installation/wizard')
            ->where('currentStep', 0)
            ->has('steps', 8));
});

test('the installer still opens when the configured session driver is database', function () {
    config(['session.driver' => 'database']);

    $this->get('/install')->assertOk();
});

test('a completed installer redirects reads to login and refuses writes', function () {
    setInstallerStatus('completed');

    $this->get('/install')->assertRedirect(route('login'));
    $this->post('/install/run', ['confirmed' => true])->assertStatus(409);
});

test('the success page is unreachable without a completion notice', function () {
    setInstallerStatus('completed');

    $this->get('/install/complete')->assertRedirect(route('login'));
});

test('a failed installation can only reach the review screen', function () {
    setInstallerStatus('failed', ['error_code' => 'MIGRATION_FAILED']);

    $this->get('/install')->assertRedirect(route('install.review'));
    $this->get('/install/requirements')->assertRedirect(route('install.review'));

    $this->get('/install/review')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('installation/wizard/review')
            ->where('status', 'failed')
            ->where('error.code', 'MIGRATION_FAILED'));
});

test('an installation in progress cannot return to the form steps', function () {
    setInstallerStatus('installing');

    $this->get('/install/requirements')->assertRedirect(route('install.review'));

    $this->get('/install/review')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('installation/wizard/review')
            ->where('status', 'installing'));
});

test('the review step redirects to the missing prerequisite step', function () {
    $this->get('/install/review')->assertRedirect(route('install.database'));

    $this->withSession(['installation.database_verified_at' => now()->toIso8601String()])
        ->get('/install/review')
        ->assertRedirect(route('install.email'));

    $this->withSession([
        'installation.database_verified_at' => now()->toIso8601String(),
        'installation.email' => [
            'mailer' => 'smtp',
            'host' => '127.0.0.1',
            'port' => 1025,
            'username' => null,
            'password' => null,
            'from_address' => 'noreply@paroki-hspmtb.local',
            'from_name' => 'Aplikasi Uji',
        ],
    ])
        ->get('/install/review')
        ->assertRedirect(route('install.super-admin'));
});
