<?php

use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Cache;

it('reads the settings cache once per request', function () {
    Cache::spy();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['parish_name' => 'Paroki Uji']);

    $service = app(SiteSettingsService::class);

    expect($service->get('parish_name'))->toBe('Paroki Uji')
        ->and($service->get('parish_name'))->toBe('Paroki Uji')
        ->and($service->get('parish_name'))->toBe('Paroki Uji');
});

it('re-reads the settings after forget', function () {
    Cache::spy();
    Cache::shouldReceive('remember')
        ->twice()
        ->andReturn(['parish_name' => 'Paroki Uji']);
    Cache::shouldReceive('forget')->once();

    $service = app(SiteSettingsService::class);

    $service->get('parish_name');
    $service->forget();
    $service->get('parish_name');
});
