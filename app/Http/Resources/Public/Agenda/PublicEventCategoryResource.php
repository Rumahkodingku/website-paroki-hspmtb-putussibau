<?php

namespace App\Http\Resources\Public\Agenda;

use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An activity category as the public filter offers it.
 *
 * `color_value` again, so the calendar can tint a day without a hex literal
 * ever appearing in a component. It is a hint and never the only marker — the
 * name travels with it in both the filter and the card.
 *
 * @mixin EventCategory
 */
class PublicEventCategoryResource extends JsonResource
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
