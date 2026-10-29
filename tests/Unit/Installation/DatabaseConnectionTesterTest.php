<?php

use App\Enums\InstallationError;
use App\Services\Installation\DatabaseConnectionTester;

test('an unsupported database driver is refused', function () {
    $result = app(DatabaseConnectionTester::class)->test([
        'driver' => 'sqlite',
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['code'])->toBe(InstallationError::DatabaseUnsupported->value);
});

test('an unreachable database returns a safe message without the password', function () {
    config([
        'installer.database.supported_drivers' => ['mysql'],
    ]);

    $result = app(DatabaseConnectionTester::class)->test([
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 1,
        'database' => 'nothing_here',
        'username' => 'nobody',
        'password' => 'super-secret-password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['code'])->toBe(InstallationError::DatabaseUnreachable->value)
        ->and($result['message'])->not->toContain('super-secret-password');
});
