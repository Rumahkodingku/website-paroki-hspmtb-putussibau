<?php

namespace App\Http\Resources\Agenda;

use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An activity category as the agenda form's dropdown and the calendar's key
 * need it.
 *
 * The colour comes through as `color_value`, already shaped as a usable CSS
 * value or null, so no hex literal has to exist in a .tsx file. See
 * EventCategoryResource for why.
 *
 * @mixin EventCategory
 */
class EventCategoryOptionResource extends JsonResource
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
            'color_value' => $this->colorValue(),
        ];
    }
}
