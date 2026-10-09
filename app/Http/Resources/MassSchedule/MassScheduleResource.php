<?php

namespace App\Http\Resources\MassSchedule;

use App\Models\MassSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A recurring mass as the admin list and edit form need it.
 *
 * start_time is sent as "07:30", not as a datetime and not as a timestamp.
 * Two reasons, and the second is the one that matters: a TIME column has no date
 * to display, and any attempt to give it one introduces a timezone conversion
 * that PRD D-02 explicitly forbids. It stays a wall clock all the way to
 * lib/datetime.ts, which formats it by replacing the colon.
 *
 * day_of_week travels as its integer value rather than its label, so the
 * TypeScript union mirrors App\Enums\DayOfWeek instead of a list of strings that
 * could be retyped wrong. The label comes from the enum on the server.
 *
 * @mixin MassSchedule
 */
class MassScheduleResource extends JsonResource
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
            'day_of_week' => $this->day_of_week->value,
            'start_time' => substr($this->start_time, 0, 5),
            'name' => $this->name,
            'language' => $this->language,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'location' => $this->nested(LocationOptionResource::class, $this->whenLoaded('location')),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
            ],
        ];
    }
}
