<?php

namespace App\Http\Resources\Pelayanan;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A category as the Kategori Pelayanan list needs it.
 *
 * `services_count` is the number the delete dialog names before the server
 * refuses (roadmap 6B-102), read from a withCount() so a row does not fire its
 * own query.
 *
 * @mixin ServiceCategory
 */
class ServiceCategoryResource extends JsonResource
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
            // "no services" from "we did not look".
            'services_count' => $this->whenCounted('services'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
