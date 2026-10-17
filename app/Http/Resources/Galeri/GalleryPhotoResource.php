<?php

namespace App\Http\Resources\Galeri;

use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One photo as the admin photo manager needs it.
 *
 * The urls are resolved from the Media row, so the caller must eager load
 * `media.variants` or strict mode throws. `url` is the medium variant the grid
 * draws and `large_url` is what the admin lightbox opens; the original is never
 * reachable, which is the point of the private-original pipeline (D-24).
 *
 * @mixin GalleryPhoto
 */
class GalleryPhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'media_id' => $this->media_id,
            'caption' => $this->caption,
            'alt_text' => $this->alt_text,
            'sort_order' => $this->sort_order,
            'url' => $this->whenLoaded('media', fn (): ?string => $this->media->url('medium')),
            'thumb_url' => $this->whenLoaded('media', fn (): ?string => $this->media->url('thumb')),
            'large_url' => $this->whenLoaded('media', fn (): ?string => $this->media->url('large')),
            'width' => $this->whenLoaded('media', fn (): ?int => $this->media->width),
            'height' => $this->whenLoaded('media', fn (): ?int => $this->media->height),
        ];
    }
}
