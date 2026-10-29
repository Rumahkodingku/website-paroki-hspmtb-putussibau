<?php

use App\Services\Installation\SystemRequirementsChecker;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $base = storage_path('framework/testing/installation-requirements');

    File::deleteDirectory($base);
    File::ensureDirectoryExists($base.'/storage', 0777, true);
    File::ensureDirectoryExists($base.'/cache', 0777, true);
    file_put_contents($base.'/manifest.json', '{}');
    file_put_contents($base.'/storage-link', '');

    config([
        'installer.requirements.paths' => [
            'storage' => $base.'/storage',
            'cache' => $base.'/cache',
            'manifest' => $base.'/manifest.json',
            'hot' => $base.'/hot',
            'storage_link' => $base.'/storage-link',
        ],
        'installer.requirements.extensions' => ['json', 'pdo'],
        'installer.requirements.warning_extensions' => ['json'],
        'installer.requirements.minimum_execution_time' => 0,
        'app.debug' => false,
        'app.url' => 'https://paroki.example.test',
        'database.default' => 'mysql',
    ]);
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/installation-requirements'));
});

test('every requirement passes in a healthy environment', function () {
    $checks = app(SystemRequirementsChecker::class)->checks();

    $failures = array_values(array_filter(
        $checks,
        fn (array $check): bool => $check['status'] === 'fail',
    ));

    expect($failures)->toBe([])
        ->and(app(SystemRequirementsChecker::class)->passes())->toBeTrue();
});

test('a missing extension fails the requirement', function () {
    config(['installer.requirements.extensions' => ['definitely_not_an_extension']]);

    $checks = app(SystemRequirementsChecker::class)->checks();
    $row = collect($checks)->firstWhere('key', 'extensions');

    expect($row['status'])->toBe('fail')
        ->and($row['detail'])->toContain('definitely_not_an_extension')
        ->and(app(SystemRequirementsChecker::class)->passes())->toBeFalse();
});

test('a missing application key fails the requirement', function () {
    config(['app.key' => null]);

    $row = collect(app(SystemRequirementsChecker::class)->checks())->firstWhere('key', 'app_key');

    expect($row['status'])->toBe('fail');
});

test('an invalid application url fails the requirement', function () {
    config(['app.url' => 'not-a-url']);

    $row = collect(app(SystemRequirementsChecker::class)->checks())->firstWhere('key', 'app_url');

    expect($row['status'])->toBe('fail');
});

test('an unwritable storage directory fails the requirement', function () {
    config([
        'installer.requirements.paths.storage' => storage_path('framework/testing/does-not-exist'),
    ]);

    $row = collect(app(SystemRequirementsChecker::class)->checks())->firstWhere('key', 'storage_writable');

    expect($row['status'])->toBe('fail');
});

test('an unsupported database driver fails the requirement', function () {
    config(['database.default' => 'sqlite']);

    $row = collect(app(SystemRequirementsChecker::class)->checks())->firstWhere('key', 'database_driver');

    expect($row['status'])->toBe('fail');
});

test('a missing optional extension is only a warning', function () {
    config(['installer.requirements.warning_extensions' => ['definitely_not_an_extension']]);

    $checks = app(SystemRequirementsChecker::class)->checks();
    $row = collect($checks)->firstWhere('key', 'extensions_optional');

    expect($row['status'])->toBe('warning')
        ->and(app(SystemRequirementsChecker::class)->passes())->toBeTrue();
});
