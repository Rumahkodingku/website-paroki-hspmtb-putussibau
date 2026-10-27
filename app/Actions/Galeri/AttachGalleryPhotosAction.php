<?php

namespace App\Actions\Galeri;

use App\Models\GalleryAlbum;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Attaches already-processed uploads to an album.
 *
 * This is an Action rather than N controller writes because it is one operation
 * that must be all-or-nothing and is idempotent by construction:
 *
 *   - the whole batch runs in a transaction,
 *   - a media id already in the album is skipped rather than violating the
 *     unique (gallery_album_id, media_id) index, so a retried attach is safe,
 *   - new photos continue from the album's current highest sort_order, so an
 *     attach never rewrites the order of photos already in place.
 *
 * The server's own rule already rejects media that is not `ready`; that check is
 * in AttachGalleryPhotosRequest, and this class assumes it ran.
 *
 * @see docs/DECISIONS.md D-65
 */
class AttachGalleryPhotosAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  list<int|string>  $mediaIds  media rows already validated as ready
     * @return int how many photos were actually attached
     */
    public function handle(GalleryAlbum $album, array $mediaIds): int
    {
        return DB::transaction(function () use ($album, $mediaIds): int {
            $existing = $album->photos()->pluck('media_id')->all();
            // max() is null on an empty album, which (int) would turn into 0 and
            // start the first photo at 1. An empty album starts at 0.
            $max = $album->photos()->max('sort_order');
            $next = $max === null ? 0 : ((int) $max) + 1;
            $attached = 0;
            $attachedIds = [];

            foreach (array_map('intval', $mediaIds) as $mediaId) {
                if (in_array($mediaId, $existing, true)) {
                    continue;
                }

                $photo = $album->photos()->create([
                    'media_id' => $mediaId,
                    'sort_order' => $next,
                ]);

                $existing[] = $mediaId;
                $attachedIds[] = $photo->getKey();
                $next++;
                $attached++;
            }

            // The album's "terakhir diperbarui" moves even though only its
            // photos changed.
            $album->touch();

            if ($attached > 0) {
                $this->audit->recordModel(
                    'galleries',
                    'attached',
                    'success',
                    $album,
                    [],
                    [],
                    [
                        'photo_ids' => $attachedIds,
                        'count' => $attached,
                    ],
                );
            }

            return $attached;
        });
    }
}
