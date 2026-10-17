<?php

namespace App\Actions\Galeri;

use App\Models\GalleryAlbum;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Writes a gallery album.
 *
 * It is an Action rather than two lines in the controller because three things
 * happen together and two of them are derived:
 *
 *   1. it stamps published_at the first time an album is published, and leaves
 *      it alone afterwards — an album keeps the date it was first shown;
 *
 *   2. it records who created and last changed the row (created_by/updated_by);
 *
 *   3. it runs the write in one transaction, so a half-saved album cannot happen.
 *
 * published_at is not a request field at all: it is derived here, never posted,
 * so a browser cannot set a date the album was not published on. The cover is
 * not touched here either — it is chosen on the photo screen and set through
 * SetGalleryCoverRequest, which is the only place that knows the photo belongs
 * to this album.
 *
 * @see docs/DECISIONS.md D-65
 */
class SaveGalleryAlbumAction
{
    /**
     * @param  array<string, mixed>  $attributes  the validated payload
     */
    public function handle(GalleryAlbum $album, array $attributes, User $actor): GalleryAlbum
    {
        return DB::transaction(function () use ($album, $attributes, $actor): GalleryAlbum {
            $attributes['published_at'] = $this->resolvePublishedAt($album, $attributes);

            if (! $album->exists) {
                $attributes['created_by'] = $actor->getKey();
            }

            $attributes['updated_by'] = $actor->getKey();

            $album->fill($attributes);
            $album->save();

            return $album;
        });
    }

    /**
     * Set once, on the first publication.
     *
     * Reading published_at from the model *before* fill() is what makes this
     * "once": a re-save while already published sees the date it was given the
     * first time and keeps it, and unpublishing does not clear it.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function resolvePublishedAt(GalleryAlbum $album, array $attributes): mixed
    {
        $willPublish = (bool) ($attributes['is_published'] ?? false);

        if ($willPublish && $album->published_at === null) {
            return now();
        }

        return $album->published_at;
    }
}
