<?php

namespace App\Http\Resources\Public\Profil;

use App\Http\Resources\MassSchedule\ResolvesNestedResources;
use App\Models\ParishBoardPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A published leadership period with its published members.
 *
 * @mixin ParishBoardPeriod
 */
class PublicBoardPeriodResource extends JsonResource
{
    use ResolvesNestedResources;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_year' => $this->start_year,
            'end_year' => $this->end_year,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'members' => $this->nestedCollection(PublicBoardMemberResource::class, $this->whenLoaded('members')),
        ];
    }
}
