<?php

namespace App\Http\Resources\MassSchedule;

use App\Models\SpecialMass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A one-off mass, reduced to what a notice's picker shows.
 *
 * Same reason as MassScheduleOptionResource: the notice list needs to name its
 * target, not reprint the whole record.
 *
 * @mixin SpecialMass
 * @mixin SpecialMass
 */
class SpecialMassOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_at' => $this->start_at->toIso8601String(),
        ];
    }
}
