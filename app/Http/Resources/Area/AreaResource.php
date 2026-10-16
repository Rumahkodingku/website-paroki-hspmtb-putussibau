<?php

namespace App\Http\Resources\Area;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An area as the Wilayah & Lingkungan list needs it.
 *
 * `type` is sent as its string value and its label, so React does not keep a
 * second copy of the Indonesian word. `parent` is only present when the relation
 * was eager loaded, which the controller does for the list and the edit form.
 *
 * @mixin Area
 */
class AreaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'description' => $this->description,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'maps_url' => $this->maps_url,
            'leader_name' => $this->leader_name,
            'contact_phone' => $this->contact_phone,
            'show_contact' => $this->show_contact,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'parent' => $this->whenLoaded('parent', fn (): ?array => $this->parent === null ? null : [
                'id' => $this->parent->getKey(),
                'name' => $this->parent->name,
                'type' => $this->parent->type->value,
            ]),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
