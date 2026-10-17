<?php

namespace App\Actions\Galeri;

use App\Models\GalleryAlbum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Renumbers an album's photos from a submitted order.
 *
 * This is an Action rather than controller code because it is the one operation
 * in the module that writes more than one row and must be all-or-nothing: a
 * reorder that updated half its rows and then hit a photo id from another album
 * would leave two photos claiming the same position. The transaction and the
 * membership check are why it exists.
 *
 * The client sends the album's full photo list in its new order, so every row's
 * sort_order is rewritten to a dense 0..n-1 and no two rows can tie.
 *
 * @see docs/DECISIONS.md D-65
 */
class ReorderGalleryPhotosAction
{
    /**
     * @param  list<int|string>  $ids  the new order, first photo first
     */
    public function handle(GalleryAlbum $album, array $ids): void
    {
        $ordered = array_map('intval', $ids);

        DB::transaction(function () use ($album, $ordered): void {
            $photos = $album->photos()
                ->whereIn('id', $ordered)
                ->get()
                ->keyBy('id');

            if ($photos->count() !== count($ordered)) {
                throw ValidationException::withMessages([
                    'ids' => 'Daftar urutan memuat foto yang bukan milik album ini.',
                ]);
            }

            foreach ($ordered as $position => $id) {
                $photos->get($id)?->update(['sort_order' => $position]);
            }

            $album->touch();
        });
    }
}
