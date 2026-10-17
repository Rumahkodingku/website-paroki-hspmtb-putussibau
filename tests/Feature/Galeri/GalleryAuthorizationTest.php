<?php

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Otorisasi modul Galeri
|--------------------------------------------------------------------------
|
| ARCHITECTURE Part D section 2: every new route needs its guest and
| unauthorized cases, not only its success path.
|
| @see docs/DECISIONS.md D-65
*/

it('redirects a guest from the album list and photo manager', function () {
    $album = GalleryAlbum::factory()->create();

    $this->get(route('galleries.index'))->assertRedirect(route('login'));
    $this->get(route('galleries.photos', $album))->assertRedirect(route('login'));
});

it('redirects a guest from creating an album', function () {
    $this->post(route('galleries.store'), [])->assertRedirect(route('login'));
});

it('refuses a signed-in user without the super admin role', function () {
    $album = GalleryAlbum::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('galleries.index'))->assertForbidden();
    $this->actingAs($user)->get(route('galleries.photos', $album))->assertForbidden();
});

it('refuses a signed-in user from storing an album', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('galleries.store'), [
            'title' => 'X',
            'slug' => 'x',
            'is_published' => false,
        ])
        ->assertForbidden();
});

it('refuses a signed-in user from mutating photos', function () {
    $album = GalleryAlbum::factory()->create();
    $photo = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);
    $media = Media::factory()->ready()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('gallery-photos.store', $album), ['media_ids' => [$media->getKey()]])->assertForbidden();
    $this->actingAs($user)->post(route('gallery-photos.reorder', $album), ['ids' => [$photo->getKey()]])->assertForbidden();
    $this->actingAs($user)->post(route('gallery-photos.cover', $album), ['photo_id' => $photo->getKey()])->assertForbidden();
    $this->actingAs($user)->patch(route('gallery-photos.update', [$album, $photo]), ['caption' => 'X'])->assertForbidden();
    $this->actingAs($user)->delete(route('gallery-photos.destroy', [$album, $photo]))->assertForbidden();
});

it('refuses a signed-in user from deleting an album', function () {
    $album = GalleryAlbum::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('galleries.destroy', $album))
        ->assertForbidden();
});
