<?php

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;
use App\Models\MediaVariant;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Galeri — detail album publik
|--------------------------------------------------------------------------
|
| Roadmap 8A section 13.3 and XC-E3. The route binds {gallery:slug} through the
| published() scope, so a draft answers 404 by not being found.
|
| @see docs/DECISIONS.md D-65
*/

it('renders a published album with its photos', function () {
    $album = GalleryAlbum::factory()->published()->create();
    GalleryPhoto::factory()->count(2)->create(['gallery_album_id' => $album->getKey()]);

    $this->get('/galeri/'.$album->slug)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/galeri/show')
            ->where('album.id', $album->getKey())
            ->where('album.photo_count', 2)
            ->has('album.photos', 2));
});

it('answers 404 for a draft album', function () {
    $album = GalleryAlbum::factory()->create();

    $this->get('/galeri/'.$album->slug)->assertNotFound();
});

it('answers 404 for a slug that does not exist', function () {
    $this->get('/galeri/album-yang-tidak-ada')->assertNotFound();
});

it('answers 404 for a soft-deleted album', function () {
    $album = GalleryAlbum::factory()->published()->create();
    $album->delete();

    $this->get('/galeri/'.$album->slug)->assertNotFound();
});

it('orders photos by their saved sort_order', function () {
    $album = GalleryAlbum::factory()->published()->create();
    $third = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey(), 'sort_order' => 2]);
    $first = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey(), 'sort_order' => 0]);
    $second = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey(), 'sort_order' => 1]);

    $this->get('/galeri/'.$album->slug)
        ->assertInertia(fn ($page) => $page
            ->where('album.photos.0.id', $first->getKey())
            ->where('album.photos.1.id', $second->getKey())
            ->where('album.photos.2.id', $third->getKey()));
});

it('does not show a photo whose media is not ready', function () {
    $album = GalleryAlbum::factory()->published()->create();
    GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);

    // A row that bypassed the attach rule: its media never finished.
    GalleryPhoto::factory()->create([
        'gallery_album_id' => $album->getKey(),
        'media_id' => Media::factory()->create()->getKey(),
    ]);

    $this->get('/galeri/'.$album->slug)
        ->assertInertia(fn ($page) => $page
            ->has('album.photos', 1)
            ->where('album.photo_count', 1));
});

it('falls back to the first photo as the cover when none is chosen', function () {
    $album = GalleryAlbum::factory()->published()->create();

    $media = Media::factory()->ready()->create();
    MediaVariant::factory()->create([
        'media_id' => $media->getKey(),
        'name' => 'medium',
        'disk' => 'public',
        'path' => 'media/variants/'.$media->getKey().'/medium.webp',
    ]);

    GalleryPhoto::factory()->create([
        'gallery_album_id' => $album->getKey(),
        'media_id' => $media->getKey(),
        'sort_order' => 0,
    ]);

    $path = 'media/variants/'.$media->getKey().'/medium.webp';

    $this->get('/galeri/'.$album->slug)
        ->assertInertia(fn ($page) => $page
            ->where('album.cover_url', Storage::disk('public')->url($path)));
});
