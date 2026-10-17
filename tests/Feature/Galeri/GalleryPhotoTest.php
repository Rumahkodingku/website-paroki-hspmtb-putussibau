<?php

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;

/*
|--------------------------------------------------------------------------
| Galeri — kelola foto album
|--------------------------------------------------------------------------
|
| Roadmap 8A section 13.2. Every photo endpoint is gated on `galleries.update`
| through the album; the tests exercise the server rules — ready media only,
| order persisted, detach leaves the media alone.
|
| @see docs/DECISIONS.md D-65
*/

it('attaches ready photos in order', function () {
    $album = GalleryAlbum::factory()->create();
    $media = Media::factory()->ready()->count(2)->create();

    $this->actingAs(superAdmin())
        ->post(route('gallery-photos.store', $album), [
            'media_ids' => $media->pluck('id')->all(),
        ])
        ->assertRedirect();

    $photos = $album->photos()->get();

    expect($photos)->toHaveCount(2)
        ->and($photos->pluck('sort_order')->all())->toBe([0, 1]);
});

it('rejects a media row that is not ready yet', function () {
    $album = GalleryAlbum::factory()->create();
    $pending = Media::factory()->create(); // Pending by default

    $this->actingAs(superAdmin())
        ->post(route('gallery-photos.store', $album), [
            'media_ids' => [$pending->getKey()],
        ])
        ->assertSessionHasErrors('media_ids.0');

    expect(GalleryPhoto::count())->toBe(0);
});

it('rejects the same media twice in one request', function () {
    $album = GalleryAlbum::factory()->create();
    $media = Media::factory()->ready()->create();

    $this->actingAs(superAdmin())
        ->post(route('gallery-photos.store', $album), [
            'media_ids' => [$media->getKey(), $media->getKey()],
        ])
        ->assertSessionHasErrors('media_ids.0');
});

it('skips a media already in the album rather than failing', function () {
    $album = GalleryAlbum::factory()->create();
    $media = Media::factory()->ready()->create();
    GalleryPhoto::factory()->create([
        'gallery_album_id' => $album->getKey(),
        'media_id' => $media->getKey(),
    ]);

    $this->actingAs(superAdmin())
        ->post(route('gallery-photos.store', $album), [
            'media_ids' => [$media->getKey()],
        ])
        ->assertSessionHasNoErrors();

    expect($album->photos()->count())->toBe(1);
});

it('persists a new photo order', function () {
    $album = GalleryAlbum::factory()->create();
    $photos = GalleryPhoto::factory()->count(3)->create(['gallery_album_id' => $album->getKey()]);
    $reversed = $photos->pluck('id')->reverse()->values()->all();

    $this->actingAs(superAdmin())
        ->post(route('gallery-photos.reorder', $album), ['ids' => $reversed])
        ->assertRedirect();

    expect($album->photos()->pluck('id')->all())->toBe($reversed);
});

it('refuses a reorder that names a photo from another album', function () {
    $album = GalleryAlbum::factory()->create();
    $own = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);
    $foreign = GalleryPhoto::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('gallery-photos.reorder', $album), [
            'ids' => [$own->getKey(), $foreign->getKey()],
        ])
        ->assertSessionHasErrors('ids');
});

it('updates a photo caption and alt text', function () {
    $album = GalleryAlbum::factory()->create();
    $photo = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('gallery-photos.update', [$album, $photo]), [
            'caption' => 'Umat berdoa',
            'alt_text' => 'Umat duduk di bangku gereja',
        ])
        ->assertSessionHasNoErrors();

    expect($photo->fresh()->caption)->toBe('Umat berdoa');
});

it('refuses to edit a photo from another album', function () {
    $album = GalleryAlbum::factory()->create();
    $foreign = GalleryPhoto::factory()->create();

    $this->actingAs(superAdmin())
        ->patch(route('gallery-photos.update', [$album, $foreign]), ['caption' => 'X'])
        ->assertSessionHasErrors('photo');
});

it('detaches a photo without deleting its media', function () {
    $album = GalleryAlbum::factory()->create();
    $photo = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);
    $media = $photo->media;

    $this->actingAs(superAdmin())
        ->delete(route('gallery-photos.destroy', [$album, $photo]))
        ->assertRedirect();

    expect(GalleryPhoto::query()->whereKey($photo->getKey())->exists())->toBeFalse()
        ->and(Media::query()->whereKey($media->getKey())->exists())->toBeTrue();
});

it('clears the album cover when the covering photo is removed', function () {
    $album = GalleryAlbum::factory()->create();
    $photo = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);
    $album->update(['cover_photo_id' => $photo->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('gallery-photos.destroy', [$album, $photo]))
        ->assertRedirect();

    expect($album->fresh()->cover_photo_id)->toBeNull();
});

it('sets the cover from a photo in the album', function () {
    $album = GalleryAlbum::factory()->create();
    $photo = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);

    $this->actingAs(superAdmin())
        ->post(route('gallery-photos.cover', $album), ['photo_id' => $photo->getKey()])
        ->assertSessionHasNoErrors();

    expect($album->fresh()->cover_photo_id)->toBe($photo->getKey());
});

it('refuses a cover photo from another album', function () {
    $album = GalleryAlbum::factory()->create();
    $foreign = GalleryPhoto::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('gallery-photos.cover', $album), ['photo_id' => $foreign->getKey()])
        ->assertSessionHasErrors('photo_id');
});
