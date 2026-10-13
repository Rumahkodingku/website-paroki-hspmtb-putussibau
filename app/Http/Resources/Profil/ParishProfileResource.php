<?php

namespace App\Http\Resources\Profil;

use App\Models\ParishProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The parish profile as the admin edit form and the public page need it.
 *
 * The public shape is a different class (Public/Profil) because what may be
 * sent to a visitor and what to an administrator are different questions
 * (ARCHITECTURE.md Part C rule 1).
 *
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
            'name' => $this->name,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'history_description' => $this->history_description,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'established_year' => $this->established_year,
            'logo_media_id' => $this->logo_media_id,
            'logo_alt' => $this->logo_alt,
            'logo_url' => $this->whenLoaded('logoMedia', fn () => $this->mediaUrl($this->logoMedia)),
            'cover_media_id' => $this->cover_media_id,
            'cover_alt' => $this->cover_alt,
            'cover_url' => $this->whenLoaded('coverMedia', fn () => $this->mediaUrl($this->coverMedia)),
            'map_url' => $this->map_url,
            'video_url' => $this->video_url,
            'is_published' => $this->is_published,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
            ],
        ];
    }
}
