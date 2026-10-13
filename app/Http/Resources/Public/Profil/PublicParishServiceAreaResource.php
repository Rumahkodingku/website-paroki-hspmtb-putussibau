<?php

namespace App\Http\Resources\Public\Profil;

use App\Http\Resources\Profil\ResolvesMediaUrl;
use App\Models\ParishServiceArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParishServiceArea
 */
class PublicParishServiceAreaResource extends JsonResource
{
    use ResolvesMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'content' => $this->content,
            'map' => $this->whenLoaded('mapMedia', fn () => $this->mediaUrl($this->mapMedia, 'large')),
            'map_url' => $this->map_url,
        ];
    }
}
