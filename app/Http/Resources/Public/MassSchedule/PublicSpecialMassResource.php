<?php

namespace App\Http\Resources\Public\MassSchedule;

use App\Models\SpecialMass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A one-off mass as a visitor sees it.
 *
 * start_at and end_at are ISO 8601 with an explicit offset. Unlike
 * start_time on a recurring mass, these are real instants and they have to be:
 * a Christmas mass is on a date, and "25 Desember, 09.00 WIB" cannot be
 * reconstructed from a bare local string without knowing what the reader's
 * clock thinks.
 *
 * @mixin SpecialMass
 */
class PublicSpecialMassResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'location' => new PublicLocationResource($this->whenLoaded('location')),
        ];
    }
}
