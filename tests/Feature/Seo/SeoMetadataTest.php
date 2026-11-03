<?php

use App\Models\Event;
use App\Models\PageSeo;
use App\Models\Post;
use App\Services\SiteSettingsService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Paroki HSPMTB Putussibau',
        'parish_short_name' => 'HSPMTB Putussibau',
        'seo_default_title' => 'Paroki HSPMTB Putussibau',
        'seo_default_description' => 'Deskripsi default paroki.',
    ]);
});

it('renders the article title and description in the initial HTML', function () {
    $post = Post::factory()->published()->create([
        'title' => 'Judul Asli',
        'slug' => 'berita-seo',
        'meta_title' => 'Judul SEO',
        'meta_description' => 'Deskripsi SEO berita.',
    ]);

    $html = $this->get('/berita/'.$post->slug)->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Judul SEO | HSPMTB Putussibau</title>')
        ->toContain('name="description" content="Deskripsi SEO berita."')
        ->toContain('rel="canonical" href="'.url('/berita/berita-seo').'"')
        ->toContain('property="og:url" content="'.url('/berita/berita-seo').'"')
        ->toContain('property="og:type" content="article"');

    expect(substr_count($html, '<title'))->toBe(1)
        ->and(substr_count($html, 'name="description"'))->toBe(1)
        ->and(substr_count($html, 'rel="canonical"'))->toBe(1)
        ->and(substr_count($html, 'name="robots"'))->toBe(1);
});

it('renders the event title and plain description in the initial HTML', function () {
    $event = Event::factory()->create([
        'title' => 'Kegiatan SEO',
        'slug' => 'agenda-seo',
        'description' => '<p>Deskripsi agenda SEO.</p>',
    ]);

    $html = $this->get('/agenda/'.$event->slug)->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Kegiatan SEO | HSPMTB Putussibau</title>')
        ->toContain('name="description" content="Deskripsi agenda SEO."')
        ->toContain('rel="canonical" href="'.url('/agenda/agenda-seo').'"');
});

it('uses a page_seo row before the configured default', function () {
    PageSeo::query()->create([
        'page_key' => 'news',
        'meta_title' => 'Berita Kustom',
        'meta_description' => 'Deskripsi kustom berita.',
    ]);

    $html = $this->get('/berita')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Berita Kustom | HSPMTB Putussibau</title>')
        ->toContain('name="description" content="Deskripsi kustom berita."');
});

it('falls back to the configured page title when no row exists', function () {
    $html = $this->get('/berita')->assertOk()->getContent();

    expect($html)->toContain('<title>Berita &amp; Artikel | HSPMTB Putussibau</title>');
});

it('drops search and filter parameters from the canonical URL', function () {
    $search = $this->get('/berita?q=misa')->assertOk()->getContent();
    $filter = $this->get('/berita?kategori=renungan')->assertOk()->getContent();

    expect($search)
        ->toContain('rel="canonical" href="'.url('/berita').'"')
        ->toContain('name="robots" content="noindex, follow"')
        ->not->toContain('canonical" href="'.url('/berita').'?q=misa"');

    expect($filter)
        ->toContain('rel="canonical" href="'.url('/berita').'"')
        ->not->toContain('canonical" href="'.url('/berita').'?kategori=renungan"');
});

it('keeps pagination in the canonical URL', function () {
    $html = $this->get('/berita?page=2')->assertOk()->getContent();

    expect($html)->toContain('rel="canonical" href="'.url('/berita').'?page=2"');
});

it('marks the admin preview as noindex in the initial HTML', function () {
    $post = Post::factory()->draft()->create();

    $html = $this->actingAs(superAdmin())
        ->get(route('posts.preview', $post))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('name="robots" content="noindex, follow"');
});

it('marks admin pages as noindex in the initial HTML', function () {
    $html = $this->actingAs(superAdmin())
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('name="robots" content="noindex, follow"');
});

it('renders exactly one set of shared metadata tags on a static page', function () {
    $html = $this->get('/jadwal-misa')->assertOk()->getContent();

    foreach (['name="description"', 'name="robots"', 'rel="canonical"', 'property="og:url"', 'property="og:title"'] as $tag) {
        expect(substr_count($html, $tag))->toBe(1);
    }
});

it('includes the expected page data in the shared seo prop', function () {
    $this->get('/berita')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.page.title', 'Berita & Artikel')
            ->where('seo.page.canonical', url('/berita'))
            ->where('seo.page.robots', 'index, follow')
            ->where('seo.page.ogType', 'website')
        );
});
