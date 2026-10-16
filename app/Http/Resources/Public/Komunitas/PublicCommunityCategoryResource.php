<?php

namespace App\Http\Resources\Public\Komunitas;

use App\Models\CommunityCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A category as a public filter option: name and slug only.
 *
 * @mixin CommunityCategory
 */
class PublicCommunityCategoryResource extends JsonResource
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
        ];
    }
}
