<?php

namespace App\Http\Resources\Public\Galeri;

use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One photo as the homepage gallery teaser needs it.
 *
 * The public gallery detail resource deliberately sends no album identity: the
 * detail page already knows which album it is on. A teaser does not, so this
 * resource adds just the title and slug needed to link back to the album.
 *
 * @mixin GalleryPhoto
 */
class PublicGalleryTeaserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'alt_text' => $this->alt_text,
            'thumb_url' => $this->whenLoaded('media', fn (): ?string => $this->media->url('thumb')),
            'url' => $this->whenLoaded('media', fn (): ?string => $this->media->url('medium')),
            'width' => $this->whenLoaded('media', fn (): ?int => $this->media->width),
            'height' => $this->whenLoaded('media', fn (): ?int => $this->media->height),
            'album' => $this->whenLoaded('album', fn (): array => [
                'title' => $this->album->title,
                'slug' => $this->album->slug,
            ]),
        ];
    }
}
