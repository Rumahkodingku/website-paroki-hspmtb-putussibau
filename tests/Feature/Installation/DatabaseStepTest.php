<?php

use App\Services\Installation\DatabaseReadinessChecker;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    configureInstaller();

    config([
        'app.url' => 'https://paroki.example.test',
        'installer.requirements.minimum_execution_time' => 0,
    ]);
});

afterEach(function () {
    resetInstallerStatusFile();
});

test('the database step shows the configured connection without the password', function () {
    $this->get('/install/database')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('installation/wizard/database')
            ->where('database.driver', 'mysql')
            ->missing('database.password'));
});

test('a successful test and a fresh database advance to the next step', function () {
    app()->instance(DatabaseReadinessChecker::class, fakeDatabaseReadiness());

    $this->post('/install/database/test', [
        'driver' => 'mysql',
        'host' => (string) config('database.connections.mysql.host'),
        'port' => (int) config('database.connections.mysql.port'),
        'database' => (string) config('database.connections.mysql.database'),
        'username' => (string) config('database.connections.mysql.username'),
        'password' => (string) config('database.connections.mysql.password'),
    ])
        ->assertRedirect(route('install.email'))
        ->assertSessionHas('installation.database_verified_at')
        ->assertSessionHas('installation.database');
});

test('a database that already has tables is refused', function () {
    app()->instance(
        DatabaseReadinessChecker::class,
        fakeDatabaseReadiness(false, 'DATABASE_NOT_FRESH'),
    );

    $this->from('/install/database')
        ->post('/install/database/test', [
            'driver' => 'mysql',
            'host' => (string) config('database.connections.mysql.host'),
            'port' => (int) config('database.connections.mysql.port'),
            'database' => (string) config('database.connections.mysql.database'),
            'username' => (string) config('database.connections.mysql.username'),
            'password' => (string) config('database.connections.mysql.password'),
        ])
        ->assertRedirect('/install/database')
        ->assertSessionHasErrors('database');

    $this->assertGuest();
});

test('an unreachable database shows a safe message', function () {
    config([
        'database.connections.mysql.host' => '127.0.0.1',
        'database.connections.mysql.port' => 1,
        'database.connections.mysql.database' => 'not_here',
        'database.connections.mysql.username' => 'nobody',
        'database.connections.mysql.password' => 'super-secret-password',
    ]);

    $response = $this->from('/install/database')->post('/install/database/test', [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 1,
        'database' => 'not_here',
        'username' => 'nobody',
        'password' => 'super-secret-password',
    ]);

    $response->assertRedirect('/install/database')
        ->assertSessionHasErrors('database');

    expect(session('errors')->first('database'))->not->toContain('super-secret-password');
});

test('the super admin step cannot be opened before a successful database test', function () {
    $this->get('/install/super-admin')->assertRedirect(route('install.database'));
});
