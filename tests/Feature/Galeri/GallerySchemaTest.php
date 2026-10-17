<?php

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Galeri — schema dan domain
|--------------------------------------------------------------------------
|
| The Gallery tables and the models above them. What is asserted here is the
| shape the decisions fixed, not that Eloquent works, because the shape is what
| a later change can quietly break:
|
|   - a photo references a Media row, not path columns (D-65 point 1),
|   - the cover points at a photo in the album (D-65 point 2),
|   - slug stays unique across soft-deleted rows, so there is no active_marker,
|   - deleting a Media removes the photo row, never the other way around.
|
| @see docs/DECISIONS.md D-65
*/

it('creates the gallery tables', function (string $table) {
    expect(Schema::hasTable($table))->toBeTrue();
})->with([
    'gallery_albums',
    'gallery_photos',
]);

it('soft deletes the album but not the photo', function () {
    expect(Schema::hasColumn('gallery_albums', 'deleted_at'))->toBeTrue()
        ->and(Schema::hasColumn('gallery_photos', 'deleted_at'))->toBeFalse();
});

it('has no generated active_marker anywhere', function (string $table) {
    // The posts/events/communities rule, not the locations rule (D-44/D-55): the
    // public route binds {gallery:slug}, so a soft-deleted row keeps its slug.
    expect(Schema::hasColumn($table, 'active_marker'))->toBeFalse();
})->with([
    'gallery_albums',
    'gallery_photos',
]);

it('stores a photo as a media reference rather than path columns', function (string $column) {
    expect(Schema::hasColumn('gallery_photos', $column))->toBeTrue();
})->with(['media_id', 'gallery_album_id', 'caption', 'alt_text', 'sort_order']);

it('does not store image paths on a photo', function (string $column) {
    expect(Schema::hasColumn('gallery_photos', $column))->toBeFalse();
})->with(['path', 'thumb_path', 'original_path', 'width', 'height', 'file_size']);

it('refuses the same photo twice in one album', function () {
    $album = GalleryAlbum::factory()->create();
    $media = Media::factory()->ready()->create();

    GalleryPhoto::factory()->create([
        'gallery_album_id' => $album->getKey(),
        'media_id' => $media->getKey(),
    ]);

    expect(fn () => GalleryPhoto::factory()->create([
        'gallery_album_id' => $album->getKey(),
        'media_id' => $media->getKey(),
    ]))->toThrow(QueryException::class);
});

it('removes the photo row when its media is deleted', function () {
    // Cascade runs from media to the join row, never the reverse (D-65 point 4):
    // MediaController::destroy() deletes the row for real, so a RESTRICT here
    // would turn "delete an image an album uses" into a 500.
    $photo = GalleryPhoto::factory()->create();
    $media = $photo->media;

    $media->delete();

    expect(GalleryPhoto::query()->whereKey($photo->getKey())->exists())->toBeFalse();
});

it('nulls the cover when the cover photo is deleted', function () {
    $album = GalleryAlbum::factory()->create();
    $photo = GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);

    $album->forceFill(['cover_photo_id' => $photo->getKey()])->save();

    $photo->delete();

    expect($album->fresh()->cover_photo_id)->toBeNull();
});

it('removes the photo rows when the album is force deleted', function () {
    $album = GalleryAlbum::factory()->create();
    $photos = GalleryPhoto::factory()->count(2)->create(['gallery_album_id' => $album->getKey()]);

    $album->forceDelete();

    expect(GalleryPhoto::query()->whereIn('id', $photos->pluck('id'))->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Scope
|--------------------------------------------------------------------------
*/

it('publishes only published albums', function () {
    $published = GalleryAlbum::factory()->published()->create();
    GalleryAlbum::factory()->create(); // draft

    expect(GalleryAlbum::published()->pluck('id')->all())->toBe([$published->getKey()]);
});

it('orders albums by event date, newest first, nulls last', function () {
    $old = GalleryAlbum::factory()->published()->create(['event_date' => '2024-01-01']);
    $new = GalleryAlbum::factory()->published()->create(['event_date' => '2025-06-01']);
    $undated = GalleryAlbum::factory()->published()->withoutDate()->create();

    expect(GalleryAlbum::ordered()->pluck('id')->all())
        ->toBe([$new->getKey(), $old->getKey(), $undated->getKey()]);
});

it('keeps a soft-deleted album slug in the table', function () {
    $album = GalleryAlbum::factory()->create(['slug' => 'natal-2025']);
    $album->delete();

    expect(GalleryAlbum::query()->where('slug', 'natal-2025')->exists())->toBeFalse()
        ->and(GalleryAlbum::withTrashed()->where('slug', 'natal-2025')->exists())->toBeTrue();
});

it('resolves a public slug only to a published album', function () {
    $draft = GalleryAlbum::factory()->create(['slug' => 'draf-album']);
    $published = GalleryAlbum::factory()->published()->create(['slug' => 'terbit-album']);

    expect((new GalleryAlbum)->resolveRouteBinding('draf-album', 'slug'))->toBeNull()
        ->and((new GalleryAlbum)->resolveRouteBinding($published->slug, 'slug')?->getKey())
        ->toBe($published->getKey());
});
