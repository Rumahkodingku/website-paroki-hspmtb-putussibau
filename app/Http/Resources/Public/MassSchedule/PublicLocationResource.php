<?php

namespace App\Http\Resources\Public\MassSchedule;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A location as a visitor sees it.
 *
 * mapsUrl is the finished link, not the raw fields, and that is the point of
 * building it here rather than in React (roadmap 8: no business calculation on
 * the client). The fallback order lives on the model — maps_url, then the
 * coordinate pair, then nothing — and this resource only forwards the answer.
 *
 * When there is no URL the field is null rather than omitted. A visitor is
 * entitled to know a location has no map link, and the React side renders
 * nothing; an absent key and a null key mean different things there.
 *
 * notes is not sent. It is an internal field: PRD 7.3 does not list it for the
 * public page, and ARCHITECTURE.md Part C rule 2 says send only what the page
 * renders.
 *
 * @mixin Location
 */
class PublicLocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'address' => $this->address,
            'mapsUrl' => $this->mapsUrl(),
        ];
    }
}
