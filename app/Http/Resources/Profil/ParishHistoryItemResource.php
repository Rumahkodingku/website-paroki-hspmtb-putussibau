<?php

namespace App\Http\Resources\Profil;

use App\Models\ParishHistoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParishHistoryItem
 */
class ParishHistoryItemResource extends JsonResource
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
            // ISO date string, or null when only the year is known.
            'date' => $this->date?->toDateString(),
            'description' => $this->description,
            'media_id' => $this->media_id,
            'image_alt' => $this->image_alt,
            'image_url' => $this->whenLoaded('media', fn () => $this->mediaUrl($this->media)),
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
