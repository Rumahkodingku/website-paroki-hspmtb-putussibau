<?php

namespace App\Http\Resources\Galeri;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An album as the edit form and the photo manager need it: every field, plus its
 * photos in order.
 *
 * `photos` and `cover_url` are resolved from the Media rows, so the controller
 * eager loads `coverPhoto.media.variants` and `photos.media.variants`.
 *
 * @mixin GalleryAlbum
 */
class GalleryAlbumResource extends JsonResource
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
            'description' => $this->description,
            'event_date' => $this->event_date?->toDateString(),
            'location' => $this->location,
            'cover_photo_id' => $this->cover_photo_id,
            'cover_url' => $this->coverMedia()?->url('medium'),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'photo_count' => $this->whenCounted('photos'),
            'photos' => $this->whenLoaded('photos', fn (): array => GalleryPhotoResource::collection($this->photos)->resolve()),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }

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
