<?php

namespace App\Http\Resources\Pelayanan;

use App\Models\ServiceRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One requirement as the admin list and the public checklist need it.
 *
 * No `can` block: the requirement is edited inside a service, and the service's
 * own `can.update` is the ability every requirement endpoint checks (D-54).
 *
 * @mixin ServiceRequirement
 */
class ServiceRequirementResource extends JsonResource
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
            'sort_order' => $this->sort_order,
        ];
    }
}
