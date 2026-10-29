<?php

use App\Services\Installation\ProductionSeederRunner;
use Tests\Fixtures\Installation\RecordingSeeder;

beforeEach(function () {
    RecordingSeeder::$ran = [];
});

test('it runs only the configured seeders, in order', function () {
    config([
        'installer.seeders.permissions' => [RecordingSeeder::class],
        'installer.seeders.categories' => [RecordingSeeder::class, RecordingSeeder::class],
    ]);

    $runner = app(ProductionSeederRunner::class);

    $runner->runPermissions();
    $runner->runCategories();

    expect(RecordingSeeder::$ran)->toHaveCount(3);
});

test('it refuses a seeder class that does not exist', function () {
    config(['installer.seeders.permissions' => ['App\\Seeders\\NotARealSeeder']]);

    expect(fn () => app(ProductionSeederRunner::class)->runPermissions())
        ->toThrow(RuntimeException::class);
});
