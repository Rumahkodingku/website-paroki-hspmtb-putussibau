<?php

namespace App\Http\Resources\Profil;

use App\Http\Resources\MassSchedule\ResolvesNestedResources;
use App\Models\ParishBoardPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParishBoardPeriod
 */
class ParishBoardPeriodResource extends JsonResource
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
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
            // What the delete dialog names before the cascade fires.
            'members_count' => $this->whenCounted('members'),
            'members' => $this->nestedCollection(ParishBoardMemberResource::class, $this->whenLoaded('members')),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
