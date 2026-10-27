<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use Database\Seeders\ServiceCategorySeeder;
use Database\Seeders\ServiceSeeder;

/*
|--------------------------------------------------------------------------
| Seeder layanan sakramen
|--------------------------------------------------------------------------
|
| ServiceSeeder kini memuat konten lengkap tujuh sakramen dan mengaktifkannya
| untuk pratinjau, menggantikan DemoServicesSeeder yang telah dihapus.
|
| @see docs/DECISIONS.md D-54
*/

it('seeds the three categories', function () {
    $this->seed(ServiceCategorySeeder::class);

    expect(ServiceCategory::query()->pluck('slug')->sort()->values()->all())
        ->toBe(['inisiasi-kristiani', 'pelayanan-persekutuan', 'penyembuhan']);
});

it('seeds the seven services active with full content', function () {
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);

    expect(Service::count())->toBe(7)
        ->and(Service::query()->where('is_active', true)->count())->toBe(7);

    foreach (Service::query()->get() as $service) {
        expect($service->requirements()->count())->toBeGreaterThan(0)
            ->and($service->steps()->count())->toBeGreaterThan(0)
            ->and($service->faqs()->count())->toBeGreaterThan(0)
            ->and($service->thumbnail_media_id)->not->toBeNull();
    }
});

it('marks the anointing of the sick service as emergency', function () {
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);

    $service = Service::query()->where('slug', 'pengurapan-orang-sakit')->sole();

    expect($service->is_emergency)->toBeTrue()
        ->and($service->serviceCategory->slug)->toBe('penyembuhan');
});

it('does not duplicate records when run twice', function () {
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);

    expect(Service::count())->toBe(7)
        ->and(ServiceCategory::count())->toBe(3);
});

it('does not overwrite an admin edit when run twice', function () {
    $this->seed(ServiceCategorySeeder::class);
    $this->seed(ServiceSeeder::class);

    $service = Service::query()->where('slug', 'baptis')->sole();
    $service->update(['title' => 'Judul Yang Diubah Admin', 'is_active' => false]);

    $this->seed(ServiceSeeder::class);

    expect($service->fresh()->title)->toBe('Judul Yang Diubah Admin')
        ->and($service->fresh()->is_active)->toBeFalse();
});
