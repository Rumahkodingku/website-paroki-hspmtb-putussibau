<?php

namespace App\Http\Resources\Agenda;

use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An activity category as the admin list and the agenda form need it.
 *
 * color is sent as `color_value`, not as `color`. The hex is shaped on the
 * server (EventCategory::colorValue()) so the component receives either a value
 * it can put in a style attribute or null — it never has to decide whether a
 * string is a colour, and no hex literal ever has to appear in a .tsx file,
 * which is what ArchitectureTest::'no component reaches for a brand colour by
 * hex value' exists to keep out.
 *
 * @mixin EventCategory
 */
class EventCategoryResource extends JsonResource
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
            'color' => $this->color,
            'color_value' => $this->colorValue(),
            'events_count' => $this->whenCounted('events'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
