<?php

namespace App\Http\Resources\MassSchedule;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A location as the admin list and edit form need it.
 *
 * active_schedules_count is the number MASS-07's warning is built from. It is
 * read from a withCount() rather than counted here: this resource runs once per
 * row, and a count query per row is an N+1 that shouldBeStrict() would not even
 * catch because it is eager loading being skipped deliberately.
 *
 * The public-facing shape is deliberately a different class — see
 * App\Http\Resources\Public\MassSchedule\PublicLocationResource. Same row, very
 * different audience, and mixing them is how an internal note ends up in the
 * page source.
 *
 * @mixin Location
 */
class LocationResource extends JsonResource
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
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'maps_url' => $this->maps_url,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            // Absent rather than zero when the count was not loaded, so the
            // admin UI can tell "no schedules" from "we did not look".
            'active_schedules_count' => $this->whenCounted('active_schedules'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
            ],
        ];
    }
}
