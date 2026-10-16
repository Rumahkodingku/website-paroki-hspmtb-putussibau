<?php

namespace App\Http\Resources\Area;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An area as a selectable option, flat with its parent named.
 *
 * The parent is carried as `parent_name` rather than nested so the area selector
 * can render "Wilayah — Lingkungan" in one line without a second lookup.
 *
 * @mixin Area
 */
class AreaOptionResource extends JsonResource
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
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'parent_id' => $this->parent_id,
            'parent_name' => $this->whenLoaded('parent', fn (): ?string => $this->parent?->name),
        ];
    }
}
