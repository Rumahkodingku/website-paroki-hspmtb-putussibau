<?php

namespace App\Http\Resources\Public\Komunitas;

use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A community as the public directory card needs it.
 *
 * The card carries no contact and no areas: the contact privacy rule (XC-P1) is
 * enforced on the detail resource, and a community no longer maps to an area
 * (D-57).
 *
 * @mixin Community
 */
class PublicCommunityCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'thumbnail_url' => $this->whenLoaded('thumbnailMedia', fn (): ?string => $this->thumbnailMedia->url('medium')),
            'logo_url' => $this->whenLoaded('logoMedia', fn (): ?string => $this->logoMedia->url('thumb')),
            'category' => $this->whenLoaded('communityCategory', fn (): array => [
                'id' => $this->communityCategory->getKey(),
                'name' => $this->communityCategory->name,
            ]),
        ];
    }
}
