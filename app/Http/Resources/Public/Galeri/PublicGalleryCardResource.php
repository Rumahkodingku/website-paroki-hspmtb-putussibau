<?php

namespace App\Http\Resources\Public\Galeri;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An album as the public directory card needs it.
 *
 * The cover is resolved server-side: the chosen cover, else the first ready
 * photo (GAL-07). The card carries no description, because the listing already
 * shows the title and date and the description is the detail page's.
 *
 * @mixin GalleryAlbum
 */
class PublicGalleryCardResource extends JsonResource
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
            'photo_count' => $this->whenCounted('photos'),
            'cover_url' => $this->coverMedia()?->url('medium'),
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
