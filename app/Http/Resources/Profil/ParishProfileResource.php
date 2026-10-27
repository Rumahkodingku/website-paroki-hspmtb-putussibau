<?php

namespace App\Http\Resources\Profil;

use App\Models\ParishProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParishProfile
 */
class ParishProfileResource extends JsonResource
{
    use ResolvesMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'established_year' => $this->established_year,
            'cover_media_id' => $this->cover_media_id,
            'cover_alt' => $this->cover_alt,
            'cover_url' => $this->whenLoaded('coverMedia', fn () => $this->mediaUrl($this->coverMedia)),
            'video_url' => $this->video_url,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
            ],
        ];
    }
}
