<?php

namespace App\Http\Resources\Public\Profil;

use App\Http\Resources\Profil\ResolvesMediaUrl;
use App\Models\PastorAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A completed or current assignment, as the public history list shows it.
 *
 * @mixin PastorAssignment
 */
class PublicPastorAssignmentResource extends JsonResource
{
    use ResolvesMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->pastor->name,
            'title' => $this->pastor->title,
            'photo' => $this->whenLoaded('pastor', fn () => $this->mediaUrl($this->pastor->photoMedia, 'large')),
            'photo_alt' => $this->pastor->photo_alt,
            'position' => $this->position->value,
            'position_label' => $this->position->label(),
            'start_year' => $this->start_year,
            'end_year' => $this->end_year,
            'is_current' => $this->is_current,
            'description' => $this->description,
        ];
    }
}
