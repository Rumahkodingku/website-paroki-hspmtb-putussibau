<?php

namespace App\Http\Resources\Public\Galeri;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An album as the public detail page needs it: its fields and its photos in
 * order.
 *
 * The photos are the ones the controller loaded already filtered to ready media,
 * so a photo whose processing failed never reaches a visitor (roadmap 8A section
 * 8.2). Optional fields are sent as null rather than an empty string, so React
 * can tell "no description" from an empty one.
 *
 * @mixin GalleryAlbum
 */
class PublicGalleryDetailResource extends JsonResource
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
            'photo_count' => $this->whenCounted('photos'),
            'cover_url' => $this->coverMedia()?->url('medium'),
            'photos' => $this->whenLoaded('photos', fn (): array => PublicGalleryPhotoResource::collection($this->photos)->resolve()),
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
