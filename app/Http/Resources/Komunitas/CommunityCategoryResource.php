<?php

namespace App\Http\Resources\Komunitas;

use App\Models\CommunityCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A category as the Kategori Komunitas list needs it.
 *
 * `communities_count` is the number the delete dialog names before the server
 * refuses (roadmap 5.1), read from a withCount() so a row does not fire its own
 * query.
 *
 * @mixin CommunityCategory
 */
class CommunityCategoryResource extends JsonResource
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
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            // Absent rather than zero when not counted, so the dialog can tell
            // "no communities" from "we did not look".
            'communities_count' => $this->whenCounted('communities'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
