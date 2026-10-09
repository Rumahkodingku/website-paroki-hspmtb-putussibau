<?php

namespace App\Http\Resources\Public\MassSchedule;

use App\Http\Resources\MassSchedule\ResolvesNestedResources;
use App\Models\MassSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A recurring mass as a visitor sees it.
 *
 * start_time crosses the wire as "07:30". It is a wall clock (PRD D-02) with no
 * date and no timezone, so formatting it here would be the first place the UTC
 * offset could be applied by accident; lib/datetime.ts does it on the other side
 * of the boundary instead, where it is a colon swap and nothing more.
 *
 * @mixin MassSchedule
 */
class PublicMassScheduleResource extends JsonResource
{
    use ResolvesNestedResources;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week->value,
            'start_time' => substr($this->start_time, 0, 5),
            'name' => $this->name,
            'language' => $this->language,
            'notes' => $this->notes,
            'location' => $this->nested(PublicLocationResource::class, $this->whenLoaded('location')),
        ];
    }
}
