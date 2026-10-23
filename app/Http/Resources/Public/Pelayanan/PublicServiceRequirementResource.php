<?php

namespace App\Http\Resources\Public\Pelayanan;

use App\Models\ServiceRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One requirement as the public checklist needs it.
 *
 * @mixin ServiceRequirement
 */
class PublicServiceRequirementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'note' => $this->note,
            'requirement_condition' => $this->requirement_condition,
            'applies_to' => $this->applies_to,
        ];
    }
}
