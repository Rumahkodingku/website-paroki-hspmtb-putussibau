<?php

use App\Services\Installation\SystemRequirementsChecker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    configureInstaller();

    $base = storage_path('framework/testing/installation-feature-requirements');

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
    File::deleteDirectory(storage_path('framework/testing/installation-feature-requirements'));
    resetInstallerStatusFile();
});

test('the requirements step renders and touches no database', function () {
    $queries = 0;

    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->get('/install/requirements')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('installation/wizard/requirements')
            ->where('passed', true)
            ->has('checks'));

    expect($queries)->toBe(0);
});

test('rechecking the requirements redirects back to the same step', function () {
    $this->from('/install/requirements')
        ->post('/install/requirements/check')
        ->assertRedirect('/install/requirements');
});

test('the checker reports a failure that blocks the step', function () {
    config(['installer.requirements.extensions' => ['definitely_not_an_extension']]);

    expect(app(SystemRequirementsChecker::class)->passes())->toBeFalse();

    $this->get('/install/requirements')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('passed', false));
});

test('a later step cannot be opened while requirements fail', function () {
    config(['installer.requirements.extensions' => ['definitely_not_an_extension']]);

    $this->get('/install/database')->assertRedirect(route('install.requirements'));
});
