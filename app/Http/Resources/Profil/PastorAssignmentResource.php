<?php

namespace App\Http\Resources\Profil;

use App\Models\PastorAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PastorAssignment
 */
class PastorAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pastor_id' => $this->pastor_id,
            // Enum to value, plus a label for display, so React never hardcodes
            // the position names.
            'position' => $this->position->value,
            'position_label' => $this->position->label(),
            'start_year' => $this->start_year,
            'end_year' => $this->end_year,
            'is_current' => $this->is_current,
            'description' => $this->description,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
