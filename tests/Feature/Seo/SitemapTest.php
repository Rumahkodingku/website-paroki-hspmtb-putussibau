<?php

use App\Models\Area;
use App\Models\Community;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Post;
use App\Models\Service;

it('serves a valid sitemap with the public URL set', function () {
    $post = Post::factory()->published()->create(['slug' => 'berita-terbit']);
    $draft = Post::factory()->draft()->create(['slug' => 'berita-draf']);
    $event = Event::factory()->past()->create(['slug' => 'agenda-lama']);
    $hiddenEvent = Event::factory()->unpublished()->create(['slug' => 'agenda-tersembunyi']);
    $service = Service::factory()->active()->create(['slug' => 'pelayanan-aktif']);
    $hiddenService = Service::factory()->create(['slug' => 'pelayanan-nonaktif']);
    $community = Community::factory()->published()->create(['slug' => 'komunitas-aktif']);
    $hiddenCommunity = Community::factory()->forming()->create(['slug' => 'komunitas-draf']);
    $area = Area::factory()->create(['slug' => 'area-aktif']);
    $hiddenArea = Area::factory()->inactive()->create(['slug' => 'area-nonaktif']);
    $gallery = GalleryAlbum::factory()->published()->create(['slug' => 'galeri-terbit']);
    $hiddenGallery = GalleryAlbum::factory()->create(['slug' => 'galeri-draf']);

    $response = $this->get('/sitemap.xml')->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $content = (string) $response->getContent();
    $xml = simplexml_load_string($content);

    expect($xml)->not->toBeFalse()
        ->and($content)
        ->toContain('http://localhost/berita')
        ->toContain('http://localhost/berita/berita-terbit')
        ->toContain('http://localhost/agenda/agenda-lama')
        ->toContain('http://localhost/pelayanan/pelayanan-aktif')
        ->toContain('http://localhost/komunitas/komunitas-aktif')
        ->toContain('http://localhost/profil/area/area-aktif')
        ->toContain('http://localhost/galeri/galeri-terbit')
        ->not->toContain($draft->slug)
        ->not->toContain($hiddenEvent->slug)
        ->not->toContain($hiddenService->slug)
        ->not->toContain($hiddenCommunity->slug)
        ->not->toContain($hiddenArea->slug)
        ->not->toContain($hiddenGallery->slug)
        ->not->toContain('/admin')
        ->not->toContain('/install')
        ->not->toContain('/ics')
        ->not->toContain('?q=')
        ->not->toContain('?kategori=')
        ->not->toContain('?page=');
});

it('adds lastmod only where a content timestamp exists', function () {
    Post::factory()->published()->create(['slug' => 'dengan-lastmod']);

    $content = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect(substr_count($content, '<lastmod>'))->toBeGreaterThan(0);
});

it('does not include a post that is scheduled in the future', function () {
    Post::factory()->scheduled()->create(['slug' => 'terjadwal']);
    Post::factory()->publishedInFuture()->create(['slug' => 'terbit-masa-depan']);

    $content = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($content)
        ->not->toContain('terjadwal')
        ->not->toContain('terbit-masa-depan');
});
