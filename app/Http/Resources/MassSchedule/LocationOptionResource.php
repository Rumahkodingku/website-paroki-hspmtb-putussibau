<?php

namespace App\Http\Resources\MassSchedule;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The minimum a select needs: enough to show a location, nothing more.
 *
 * Used by every dropdown in the module — the location picker on a schedule, the
 * destination picker on a duplicate — and by the public filter. Address,
 * coordinates and notes are deliberately absent: they are editable fields, not
 * choice labels, and shipping them into five selects is five copies of data
 * nobody renders.
 *
 * @mixin Location
 */
class LocationOptionResource extends JsonResource
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
        ];
    }
}
