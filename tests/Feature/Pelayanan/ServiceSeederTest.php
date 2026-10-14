<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use Database\Seeders\ServiceCategorySeeder;
use Database\Seeders\ServiceSeeder;

/*
|--------------------------------------------------------------------------
| Seeder enam layanan
|--------------------------------------------------------------------------
|
| Recipe 6B-265/266/287 and D-54: structure only, all inactive, no fake form.
|
| @see docs/DECISIONS.md D-54
*/

it('seeds the three categories', function () {
    $this->seed(ServiceCategorySeeder::class);

    expect(ServiceCategory::query()->pluck('slug')->sort()->values()->all())
        ->toBe(['inisiasi', 'orang-sakit', 'perkawinan']);
});

it('seeds the six services inactive with no form URL', function () {
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);

    expect(Service::count())->toBe(6)
        ->and(Service::query()->where('is_active', true)->count())->toBe(0)
        ->and(Service::query()->whereNotNull('form_url')->count())->toBe(0);
});

it('marks the anointing of the sick service as emergency', function () {
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);

    $service = Service::query()->where('slug', 'perminyakan-orang-sakit')->sole();

    expect($service->is_emergency)->toBeTrue()
        ->and($service->serviceCategory->slug)->toBe('orang-sakit');
});

it('does not duplicate records when run twice', function () {
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);

    expect(Service::count())->toBe(6)
        ->and(ServiceCategory::count())->toBe(3);
});

it('does not overwrite an admin edit when run twice', function () {
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);

    $service = Service::query()->where('slug', 'baptis-bayi')->sole();
    $service->update(['title' => 'Judul Yang Diubah Admin', 'is_active' => true]);

    $this->seed(ServiceSeeder::class);

    expect($service->fresh()->title)->toBe('Judul Yang Diubah Admin')
        ->and($service->fresh()->is_active)->toBeTrue();
});
