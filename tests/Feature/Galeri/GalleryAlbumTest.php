<?php

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;

/*
|--------------------------------------------------------------------------
| Galeri — CRUD album
|--------------------------------------------------------------------------
|
| Roadmap 8A section 13.1. The write path is SaveGalleryAlbumAction, so the
| tests assert both the persisted row and the derived fields (slug, published_at,
| audit columns).
|
| @see docs/DECISIONS.md D-65
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function galleryPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Natal 2025',
        'slug' => 'natal-2025',
        'description' => 'Perayaan Natal bersama umat.',
        'event_date' => '2025-12-25',
        'location' => 'Gereja Paroki HSPMTB',
        'is_published' => false,
    ], $overrides);
}

it('creates an album and lands on its photo manager', function () {
    $this->actingAs(superAdmin())
        ->post(route('galleries.store'), galleryPayload())
        ->assertRedirect(route('galleries.photos', GalleryAlbum::query()->sole()));

    $album = GalleryAlbum::query()->sole();

    expect($album->title)->toBe('Natal 2025')
        ->and($album->slug)->toBe('natal-2025')
        ->and($album->is_published)->toBeFalse()
        ->and($album->published_at)->toBeNull()
        ->and($album->created_by)->not->toBeNull();
});

it('derives the slug from the title when it is left empty', function () {
    $this->actingAs(superAdmin())
        ->post(route('galleries.store'), galleryPayload(['slug' => '', 'title' => 'Misa Paskah 2026']))
        ->assertSessionHasNoErrors();

    expect(GalleryAlbum::query()->sole()->slug)->toBe('misa-paskah-2026');
});

it('rejects invalid input and persists nothing', function () {
    $this->actingAs(superAdmin())
        ->post(route('galleries.store'), galleryPayload([
            'title' => '',
            'slug' => 'bukan slug!',
        ]))
        ->assertSessionHasErrors(['title', 'slug']);

    expect(GalleryAlbum::count())->toBe(0);
});

it('rejects a duplicate slug, including a soft-deleted album', function () {
    $deleted = GalleryAlbum::factory()->create(['slug' => 'natal-2025']);
    $deleted->delete();

    $this->actingAs(superAdmin())
        ->post(route('galleries.store'), galleryPayload(['slug' => 'natal-2025']))
        ->assertSessionHasErrors('slug');
});

it('updates an album without losing its photos', function () {
    $album = GalleryAlbum::factory()->create();
    $photos = GalleryPhoto::factory()->count(2)->create(['gallery_album_id' => $album->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('galleries.update', $album), galleryPayload(['title' => 'Judul Baru', 'slug' => $album->slug]))
        ->assertRedirect(route('galleries.edit', $album));

    expect($album->fresh()->title)->toBe('Judul Baru')
        ->and($album->photos()->count())->toBe(2)
        ->and(GalleryPhoto::query()->whereIn('id', $photos->pluck('id'))->count())->toBe(2);
});

it('opens the edit page with the photo count', function () {
    $album = GalleryAlbum::factory()->create();
    GalleryPhoto::factory()->count(2)->create(['gallery_album_id' => $album->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('galleries.edit', $album))
        ->assertInertia(fn ($page) => $page
            ->component('admin/galeri/ubah')
            ->where('album.photo_count', 2));
});

it('opens the photo manager with the photo count', function () {
    $album = GalleryAlbum::factory()->create();
    GalleryPhoto::factory()->count(3)->create(['gallery_album_id' => $album->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('galleries.photos', $album))
        ->assertInertia(fn ($page) => $page
            ->component('admin/galeri/foto')
            ->where('album.photo_count', 3));
});

it('sets published_at once and keeps it across later saves', function () {
    $album = GalleryAlbum::factory()->create();

    $this->actingAs(superAdmin())
        ->patch(route('galleries.update', $album), galleryPayload([
            'slug' => $album->slug,
            'is_published' => true,
        ]))
        ->assertSessionHasNoErrors();

    $firstPublishedAt = $album->fresh()->published_at;
    expect($firstPublishedAt)->not->toBeNull();

    $this->travel(1)->minutes();

    $this->actingAs(superAdmin())
        ->patch(route('galleries.update', $album), galleryPayload([
            'title' => 'Diubah Lagi',
            'slug' => $album->slug,
            'is_published' => true,
        ]))
        ->assertSessionHasNoErrors();

    expect($album->fresh()->published_at->equalTo($firstPublishedAt))->toBeTrue();
});

it('does not clear published_at when unpublished', function () {
    $album = GalleryAlbum::factory()->published()->create();

    $this->actingAs(superAdmin())
        ->patch(route('galleries.update', $album), galleryPayload([
            'slug' => $album->slug,
            'is_published' => false,
        ]))
        ->assertSessionHasNoErrors();

    expect($album->fresh()->is_published)->toBeFalse()
        ->and($album->fresh()->published_at)->not->toBeNull();
});

it('soft deletes an album', function () {
    $album = GalleryAlbum::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('galleries.destroy', $album))
        ->assertRedirect(route('galleries.index'));

    expect(GalleryAlbum::query()->count())->toBe(0)
        ->and(GalleryAlbum::withTrashed()->count())->toBe(1);
});

it('lists albums with a photo count and cover url', function () {
    $album = GalleryAlbum::factory()->create();
    GalleryPhoto::factory()->count(3)->create(['gallery_album_id' => $album->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('galleries.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/galeri')
            ->has('albums.data', 1)
            ->where('albums.data.0.photo_count', 3)
            ->has('albums.data.0.cover_url'));
});

it('filters the admin list by status and year', function () {
    GalleryAlbum::factory()->published()->create(['event_date' => '2025-01-01']);
    GalleryAlbum::factory()->create(['event_date' => '2024-01-01']);

    $this->actingAs(superAdmin())
        ->get(route('galleries.index', ['status' => 'published']))
        ->assertInertia(fn ($page) => $page->has('albums.data', 1));

    $this->actingAs(superAdmin())
        ->get(route('galleries.index', ['tahun' => '2024']))
        ->assertInertia(fn ($page) => $page->has('albums.data', 1));
});

it('ignores an unknown sort key rather than failing', function () {
    GalleryAlbum::factory()->create();

    $this->actingAs(superAdmin())
        ->get(route('galleries.index', ['sort' => 'deleted_at', 'direction' => 'desc']))
        ->assertOk();
});

it('records the acting user as the last editor', function () {
    $admin = superAdmin();
    $album = GalleryAlbum::factory()->create();

    $this->actingAs($admin)
        ->patch(route('galleries.update', $album), galleryPayload(['slug' => $album->slug]))
        ->assertSessionHasNoErrors();

    expect($album->fresh()->updated_by)->toBe($admin->getKey());
});
