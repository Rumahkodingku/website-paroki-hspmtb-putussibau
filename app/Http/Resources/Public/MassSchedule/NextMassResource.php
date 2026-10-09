<?php

namespace App\Http\Resources\Public\MassSchedule;

use App\Support\NextMass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The "Misa Berikutnya" card's data.
 *
 * Wraps NextMass rather than flattening it at the call site, so the resolver's
 * answer and the payload the browser receives are defined in one place. starts_at
 * is already in the display timezone here: the resolver converted it once when
 * it compared candidates, and sending the UTC instant would make React convert it
 * again — which is where "misa berikutnya" quietly drifts by a day.
 *
 * @mixin NextMass
 */
class NextMassResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kind' => $this->kind->value,
            'starts_at' => $this->startsAtInDisplayTimezone()->toIso8601String(),
            'name' => $this->name,
            'language' => $this->language,
            'location' => new PublicLocationResource($this->location),
        ];
    }
}
