<?php

namespace App\Models;

use App\Enums\MediaStatus;
use Database\Factories\GalleryPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One photo in a gallery album.
 *
 * The photo is a reference to a Media row, not a set of path columns (D-65
 * point 1). The album contributes the ordering, caption and alt text; the
 * pipeline contributes the file. Detaching a photo deletes this row and leaves
 * the Media, and its file, alone.
 *
 * @property int $id
 * @property int $gallery_album_id
 * @property int $media_id
 * @property string|null $caption
 * @property string|null $alt_text
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read GalleryAlbum $album
 * @property-read Media $media
 */
#[Fillable([
    'gallery_album_id',
    'media_id',
    'caption',
    'alt_text',
    'sort_order',
])]
class GalleryPhoto extends Model
{
    /** @use HasFactory<GalleryPhotoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<GalleryAlbum, $this>
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(GalleryAlbum::class, 'gallery_album_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Photos whose media has finished processing.
     *
     * Attaching already requires a ready media row, so this is defensive on the
     * read path rather than the normal case: it is what keeps a photo whose job
     * later failed from reaching a public page (roadmap 8A section 8.2).
     *
     * @param  Builder<GalleryPhoto>  $query
     * @return Builder<GalleryPhoto>
     */
    public function scopeReady(Builder $query): Builder
    {
        return $query->whereHas(
            'media',
            fn (Builder $media): Builder => $media->where('status', MediaStatus::Ready->value),
        );
    }
}
