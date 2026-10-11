<?php

namespace App\Http\Resources\MassSchedule;

use App\Models\SpecialMass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A one-off mass as the admin list and edit form need it.
 *
 * start_at and end_at are ISO 8601 with an offset, not a bare local string: the
 * browser receives them and renders them in Asia/Pontianak itself (D-01), and a
 * string without an offset is a value with two meanings. This is the one place
 * in the module where a timezone is genuinely carried in the payload.
 *
 * @mixin SpecialMass
 */
class SpecialMassResource extends JsonResource
{
    use ResolvesNestedResources;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'title' => $this->title,
            'description' => $this->description,
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'location' => $this->nested(LocationOptionResource::class, $this->whenLoaded('location')),
            // How many notices point here, which the delete dialog reports as
            // what would stop being published. See D-40.
            'schedule_notices_count' => $this->whenCounted('scheduleNotices'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
