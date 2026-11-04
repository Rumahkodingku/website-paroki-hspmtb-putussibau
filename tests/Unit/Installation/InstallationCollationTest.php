<?php

use App\Services\Installation\InstallationRunner;

test('the installer connection uses the configured database collation', function () {
    config(['database.connections.mysql.collation' => 'utf8mb4_0900_ai_ci']);

    $runner = app(InstallationRunner::class);
    $method = new ReflectionMethod($runner, 'connectionConfigFromInput');

    $config = $method->invoke($runner, [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'paroki_test',
        'username' => 'paroki',
        'password' => 'secret',
    ]);

    expect($config['collation'])->toBe('utf8mb4_0900_ai_ci');
});
