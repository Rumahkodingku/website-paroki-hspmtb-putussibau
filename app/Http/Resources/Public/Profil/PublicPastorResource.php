<?php

namespace App\Http\Resources\Public\Profil;

use App\Http\Resources\Profil\ResolvesMediaUrl;
use App\Models\Pastor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A pastor currently in office, with the assignment that puts them there.
 *
 * @mixin Pastor
 */
class PublicPastorResource extends JsonResource
{
    use ResolvesMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assignment = $this->currentAssignment;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'photo' => $this->whenLoaded('photoMedia', fn () => $this->mediaUrl($this->photoMedia, 'large')),
            'photo_alt' => $this->photo_alt,
            'bio' => $this->bio,
            // Null-safe: a published pastor with no current assignment still
            // renders, with the position left blank rather than crashing.
            'position' => $assignment?->position->value,
            'position_label' => $assignment?->position->label(),
            'start_year' => $assignment?->start_year,
            'end_year' => $assignment?->end_year,
        ];
    }
}
