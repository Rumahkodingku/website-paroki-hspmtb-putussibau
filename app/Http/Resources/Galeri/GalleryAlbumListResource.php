<?php

namespace App\Http\Resources\Galeri;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An album as the admin list needs it: enough for the row, not the photos.
 *
 * `cover_url` prefers the chosen cover and falls back to the first photo by
 * sort_order (GAL-07). The controller eager loads `coverPhoto.media.variants`
 * and `photos.media.variants` so the fallback never lazy loads under strict
 * mode.
 *
 * @mixin GalleryAlbum
 */
class GalleryAlbumListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'event_date' => $this->event_date?->toDateString(),
            'location' => $this->location,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'photo_count' => $this->whenCounted('photos'),
            'cover_url' => $this->coverMedia()?->url('medium'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }

    /**
     * The cover Media: the chosen cover, else the first photo.
     *
     * Guarded by relationLoaded because a resource must never trigger a lazy
     * load (Eloquent strict mode throws), and a caller that forgot to eager load
     * should get no picture rather than a 500.
     */
    private function coverMedia(): ?Media
    {
        $photo = $this->relationLoaded('coverPhoto') ? $this->coverPhoto : null;

        if ($photo === null && $this->relationLoaded('photos')) {
            $photo = $this->photos->first();
        }

        return $photo instanceof GalleryPhoto && $photo->relationLoaded('media')
            ? $photo->media
            : null;
    }
}
