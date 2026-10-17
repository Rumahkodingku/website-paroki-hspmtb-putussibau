<?php

namespace App\Http\Resources\Public\Galeri;

use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One photo as the public gallery grid and lightbox need it.
 *
 * Sends no media metadata and no album id: the grid draws the medium variant, the
 * lightbox opens the large one, and the original is never reachable (D-24).
 *
 * @mixin GalleryPhoto
 */
class PublicGalleryPhotoResource extends JsonResource
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
            'url' => $this->whenLoaded('media', fn (): ?string => $this->media->url('medium')),
            'thumb_url' => $this->whenLoaded('media', fn (): ?string => $this->media->url('thumb')),
            'large_url' => $this->whenLoaded('media', fn (): ?string => $this->media->url('large')),
            'width' => $this->whenLoaded('media', fn (): ?int => $this->media->width),
            'height' => $this->whenLoaded('media', fn (): ?int => $this->media->height),
        ];
    }
}
