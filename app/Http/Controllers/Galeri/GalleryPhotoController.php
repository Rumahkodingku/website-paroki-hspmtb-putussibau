<?php

namespace App\Http\Controllers\Galeri;

use App\Actions\Galeri\AttachGalleryPhotosAction;
use App\Actions\Galeri\ReorderGalleryPhotosAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Galeri\AttachGalleryPhotosRequest;
use App\Http\Requests\Galeri\ReorderGalleryPhotosRequest;
use App\Http\Requests\Galeri\SetGalleryCoverRequest;
use App\Http\Requests\Galeri\UpdateGalleryPhotoRequest;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The photo endpoints for an album.
 *
 * Every one of them is authorized on `galleries.update` through the album, not
 * on a permission of its own: a photo has no list page, so a second permission
 * group would be a name nothing maps to a screen (D-54, D-55 point 11, D-65).
 *
 * Detaching is one row delete. The cover is cleared for free: the
 * gallery_albums.cover_photo_id foreign key is ON DELETE SET NULL, so removing
 * the photo that was the cover leaves the album with no cover and the read path
 * falls back to the first photo (GAL-07).
 *
 * @see docs/DECISIONS.md D-65
 */
class GalleryPhotoController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(
        AttachGalleryPhotosRequest $request,
        GalleryAlbum $gallery,
        AttachGalleryPhotosAction $action,
    ): RedirectResponse {
        $action->handle($gallery, $request->validated('media_ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('galeri.flash.photos_added')]);

        return back();
    }

    public function update(
        UpdateGalleryPhotoRequest $request,
        GalleryAlbum $gallery,
        GalleryPhoto $photo,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $photo): void {
            $before = $this->audit->snapshot($photo, 'gallery_photos');
            $photo->update($request->validated());
            $this->audit->updated('gallery_photos', $photo, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('galeri.flash.photo_updated')]);

        return back();
    }

    public function reorder(
        ReorderGalleryPhotosRequest $request,
        GalleryAlbum $gallery,
        ReorderGalleryPhotosAction $action,
    ): RedirectResponse {
        $action->handle($gallery, $request->validated('ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('galeri.flash.photos_reordered')]);

        return back();
    }

    public function cover(SetGalleryCoverRequest $request, GalleryAlbum $gallery): RedirectResponse
    {
        DB::transaction(function () use ($request, $gallery): void {
            $before = $this->audit->snapshot($gallery, 'galleries');
            $gallery->update(['cover_photo_id' => $request->validated('photo_id')]);
            $this->audit->updated('galleries', $gallery, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('galeri.flash.cover_updated')]);

        return back();
    }

    public function destroy(GalleryAlbum $gallery, GalleryPhoto $photo): RedirectResponse
    {
        Gate::authorize('update', $gallery);

        // The binding resolves a photo by id globally, so a photo from another
        // album could be targeted. A 404 is the honest answer: it is not a photo
        // "in this album" that the caller is allowed to act on.
        abort_unless($photo->gallery_album_id === $gallery->getKey(), 404);

        DB::transaction(function () use ($photo): void {
            $before = $this->audit->snapshot($photo, 'gallery_photos');
            $photo->delete();
            $this->audit->deleted('gallery_photos', $photo, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('galeri.flash.photo_removed')]);

        return back();
    }
}
