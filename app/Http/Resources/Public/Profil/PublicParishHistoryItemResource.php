<?php

namespace App\Http\Resources\Public\Profil;

use App\Http\Resources\Profil\ResolvesMediaUrl;
use App\Models\ParishHistoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParishHistoryItem
 */
class PublicParishHistoryItemResource extends JsonResource
{
    use ResolvesMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'year' => $this->year,
            'date' => $this->date?->toDateString(),
            'description' => $this->description,
            'image' => $this->whenLoaded('media', fn () => $this->mediaUrl($this->media)),
            'image_alt' => $this->image_alt,
        ];
    }
}
