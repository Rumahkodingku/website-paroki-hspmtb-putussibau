<?php

namespace App\Http\Resources\Profil;

use App\Models\ParishBoardMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParishBoardMember
 */
class ParishBoardMemberResource extends JsonResource
{
    use ResolvesMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parish_board_period_id' => $this->parish_board_period_id,
            'name' => $this->name,
            'position' => $this->position,
            'photo_media_id' => $this->photo_media_id,
            'photo_alt' => $this->photo_alt,
            'photo_url' => $this->whenLoaded('photoMedia', fn () => $this->mediaUrl($this->photoMedia)),
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
