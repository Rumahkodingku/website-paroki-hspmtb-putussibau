<?php

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;

/*
|--------------------------------------------------------------------------
| Galeri — direktori publik
|--------------------------------------------------------------------------
|
| Roadmap 8A section 13.3. Only published albums reach the page, and a draft
| never appears in the props — not filtered in React, not queried at all.
|
| @see docs/DECISIONS.md D-65
*/

it('lists only published albums', function () {
    $published = GalleryAlbum::factory()->published()->create();
    GalleryAlbum::factory()->create(); // draft

    $this->get('/galeri')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/galeri')
            ->has('albums.data', 1)
            ->where('albums.data.0.id', $published->getKey()));
});

it('does not leak a draft album into the props', function () {
    GalleryAlbum::factory()->create(['title' => 'Album Rahasia']);

    $this->get('/galeri')
        ->assertInertia(fn ($page) => $page
            ->has('albums.data', 0)
            ->missing('albums.data.0'));
});

it('shows a photo count and a cover url', function () {
    $album = GalleryAlbum::factory()->published()->create();
    GalleryPhoto::factory()->count(2)->create(['gallery_album_id' => $album->getKey()]);

    $this->get('/galeri')
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.photo_count', 2)
            ->has('albums.data.0.cover_url'));
});

it('filters the directory by year', function () {
    GalleryAlbum::factory()->published()->create(['event_date' => '2025-03-01']);
    GalleryAlbum::factory()->published()->create(['event_date' => '2024-03-01']);

    $this->get('/galeri?tahun=2025')
        ->assertInertia(fn ($page) => $page->has('albums.data', 1));

    $this->get('/galeri')
        ->assertInertia(fn ($page) => $page->has('albums.data', 2));
});

it('excludes a soft-deleted album', function () {
    $album = GalleryAlbum::factory()->published()->create();
    $album->delete();

    $this->get('/galeri')
        ->assertInertia(fn ($page) => $page->has('albums.data', 0));
});

it('answers 404 for a draft album slug', function () {
    $album = GalleryAlbum::factory()->create(['slug' => 'album-draf']);

    $this->get('/galeri/'.$album->slug)->assertNotFound();
});

it('orders the directory by newest event date', function () {
    $old = GalleryAlbum::factory()->published()->create(['event_date' => '2024-01-01']);
    $new = GalleryAlbum::factory()->published()->create(['event_date' => '2025-01-01']);

    $this->get('/galeri')
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.id', $new->getKey())
            ->where('albums.data.1.id', $old->getKey()));
});
