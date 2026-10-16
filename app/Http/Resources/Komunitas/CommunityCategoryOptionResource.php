<?php

namespace App\Http\Resources\Komunitas;

use App\Models\CommunityCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A category as a dropdown option: just enough to pick one.
 *
 * @mixin CommunityCategory
 */
class CommunityCategoryOptionResource extends JsonResource
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
