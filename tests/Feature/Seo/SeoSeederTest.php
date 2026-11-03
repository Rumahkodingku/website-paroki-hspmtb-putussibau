<?php

use App\Models\PageSeo;
use App\Services\SiteSettingsService;
use Database\Seeders\SeoSeeder;

it('seeds every static page and the global Open Graph fallback', function () {
    $this->seed(SeoSeeder::class);

    expect(PageSeo::query()->count())->toBe(18)
        ->and(PageSeo::query()->where('page_key', 'news')->value('meta_title'))
        ->toBe('Berita & Artikel')
        ->and(PageSeo::query()->where('page_key', 'news')->value('meta_description'))
        ->not->toBeNull()
        ->and(app(SiteSettingsService::class)->get('seo_default_og_image'))
        ->toBe('/hero-section.png');
});

it('uses the parish identity for the homepage', function () {
    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Paroki HSPMTB Putussibau',
        'short_description' => 'Komunitas umat Katolik di Putussibau.',
    ]);

    $this->seed(SeoSeeder::class);

    expect(PageSeo::query()->where('page_key', 'home')->value('meta_title'))
        ->toBe('Paroki HSPMTB Putussibau')
        ->and(PageSeo::query()->where('page_key', 'home')->value('meta_description'))
        ->toBe('Komunitas umat Katolik di Putussibau.');
});

it('does not overwrite an existing page override or global image', function () {
    PageSeo::query()->create([
        'page_key' => 'news',
        'meta_title' => 'Judul Pilihan Admin',
        'meta_description' => 'Deskripsi pilihan admin.',
    ]);
    app(SiteSettingsService::class)->set('seo_default_og_image', '/custom-og.webp');

    $this->seed(SeoSeeder::class);

    expect(PageSeo::query()->where('page_key', 'news')->value('meta_title'))
        ->toBe('Judul Pilihan Admin')
        ->and(PageSeo::query()->where('page_key', 'news')->value('meta_description'))
        ->toBe('Deskripsi pilihan admin.')
        ->and(app(SiteSettingsService::class)->get('seo_default_og_image'))
        ->toBe('/custom-og.webp')
        ->and(PageSeo::query()->count())->toBe(18);
});

it('can fill a previously blank homepage after the identity is saved', function () {
    $this->seed(SeoSeeder::class);

    expect(PageSeo::query()->where('page_key', 'home')->value('meta_title'))->toBeNull();

    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Paroki HSPMTB Putussibau',
        'short_description' => 'Komunitas umat Katolik di Putussibau.',
    ]);

    $this->seed(SeoSeeder::class);

    expect(PageSeo::query()->where('page_key', 'home')->value('meta_title'))
        ->toBe('Paroki HSPMTB Putussibau')
        ->and(PageSeo::query()->where('page_key', 'home')->value('meta_description'))
        ->toBe('Komunitas umat Katolik di Putussibau.')
        ->and(PageSeo::query()->count())->toBe(18);
});
