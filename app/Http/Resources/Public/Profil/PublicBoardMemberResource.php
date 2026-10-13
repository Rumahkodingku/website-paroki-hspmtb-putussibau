<?php

namespace App\Http\Resources\Public\Profil;

use App\Http\Resources\Profil\ResolvesMediaUrl;
use App\Models\ParishBoardMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParishBoardMember
 */
class PublicBoardMemberResource extends JsonResource
{
    use ResolvesMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'photo' => $this->whenLoaded('photoMedia', fn () => $this->mediaUrl($this->photoMedia)),
            'photo_alt' => $this->photo_alt,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
        ];
    }
}
