<?php

namespace App\Http\Resources\Profil;

use App\Models\ParishServiceArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParishServiceArea
 */
class ParishServiceAreaResource extends JsonResource
{
    use ResolvesMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'map_media_id' => $this->map_media_id,
            'map_url' => $this->map_url,
            'map_media_url' => $this->whenLoaded('mapMedia', fn () => $this->mediaUrl($this->mapMedia)),
            'is_published' => $this->is_published,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
            ],
        ];
    }
}
