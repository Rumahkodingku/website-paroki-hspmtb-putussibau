<?php

namespace App\Http\Resources\Profil;

use App\Http\Resources\MassSchedule\ResolvesNestedResources;
use App\Models\Pastor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pastor
 */
class PastorResource extends JsonResource
{
    use ResolvesMediaUrl;
    use ResolvesNestedResources;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'photo_media_id' => $this->photo_media_id,
            'photo_alt' => $this->photo_alt,
            'photo_url' => $this->whenLoaded('photoMedia', fn () => $this->mediaUrl($this->photoMedia)),
            'bio' => $this->bio,
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'current_assignment' => $this->nested(PastorAssignmentResource::class, $this->whenLoaded('currentAssignment')),
            'assignments' => $this->nestedCollection(PastorAssignmentResource::class, $this->whenLoaded('assignments')),
            'assignments_count' => $this->whenCounted('assignments'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
