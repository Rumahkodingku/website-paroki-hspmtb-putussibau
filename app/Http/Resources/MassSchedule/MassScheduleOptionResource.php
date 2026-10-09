<?php

namespace App\Http\Resources\MassSchedule;

use App\Models\MassSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A recurring mass, reduced to what a notice's picker shows.
 *
 * Separate from MassScheduleResource because this one is embedded in a list of
 * notices: shipping every schedule's notes, language and active flag for every
 * row of the notice table would be a payload nobody reads.
 *
 * @mixin MassSchedule
 */
class MassScheduleOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'day_of_week' => $this->day_of_week->value,
            'start_time' => substr($this->start_time, 0, 5),
        ];
    }
}
