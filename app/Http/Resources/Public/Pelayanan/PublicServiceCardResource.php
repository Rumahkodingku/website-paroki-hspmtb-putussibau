<?php

namespace App\Http\Resources\Public\Pelayanan;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A service as a card on /pelayanan and in the "Layanan Lainnya" strip.
 *
 * Only what a card renders. No contact numbers, no form URL, no internal flags —
 * a card that carries more than it shows is a card whose extra fields end up in
 * the page source for no reason.
 *
 * @mixin Service
 */
class PublicServiceCardResource extends JsonResource
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
            'thumbnail' => $this->whenLoaded('thumbnailMedia', fn (): ?string => $this->thumbnailMedia->url('medium')),
            'thumbnail_alt' => $this->thumbnail_alt,
            'summary' => $this->summary,
            'is_emergency' => $this->is_emergency,
        ];
    }
}
