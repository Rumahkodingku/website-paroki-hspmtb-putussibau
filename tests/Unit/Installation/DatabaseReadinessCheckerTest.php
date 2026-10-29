<?php

use App\Enums\InstallationError;
use App\Services\Installation\DatabaseReadinessChecker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->databaseName = 'paroki_installer_probe_'.Str::lower(Str::random(10));

    DB::statement(sprintf(
        'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci',
        $this->databaseName,
    ));

    $this->config = [
        ...(array) config('database.connections.mysql'),
        'database' => $this->databaseName,
    ];

    DB::purge('mysql');
});

afterEach(function () {
    DB::statement(sprintf('DROP DATABASE IF EXISTS `%s`', $this->databaseName));
});

test('a database with no tables is ready', function () {
    $result = (new DatabaseReadinessChecker($this->config))->check();

    expect($result['ok'])->toBeTrue()
        ->and($result['code'])->toBeNull();
});

test('a database with a table is not fresh', function () {
    $connection = DB::connection();
    // Use a separate connection config pointing at the throwaway database.
    $pdo = new PDO(
        sprintf('mysql:host=127.0.0.1;port=3306;dbname=%s', $this->databaseName),
        (string) config('database.connections.mysql.username'),
        (string) config('database.connections.mysql.password'),
    );
    $pdo->exec('CREATE TABLE someones_data (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)');

    $result = (new DatabaseReadinessChecker($this->config))->check();

    expect($result['ok'])->toBeFalse()
        ->and($result['code'])->toBe(InstallationError::DatabaseNotFresh->value)
        ->and($result['message'])->toContain('1 tabel');
});

test('an unreachable database returns a safe error', function () {
    $config = [
        ...(array) config('database.connections.mysql'),
        'host' => '127.0.0.1',
        'port' => 1,
        'database' => 'nothing_here',
    ];

    $result = (new DatabaseReadinessChecker($config))->check();

    expect($result['ok'])->toBeFalse()
        ->and($result['code'])->toBe(InstallationError::DatabaseUnreachable->value);
});
