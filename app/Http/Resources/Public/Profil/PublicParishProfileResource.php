<?php

namespace App\Http\Resources\Public\Profil;

use App\Http\Resources\Profil\ResolvesMediaUrl;
use App\Models\ParishProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The parish profile as a visitor may read it.
 *
 * A separate class from the admin resource on purpose: what a visitor may see
 * and what an administrator may see are different questions (ARCHITECTURE.md
 * Part C rule 1). Only published rows reach here, enforced by the controller's
 * scope.
 *
 * @mixin ParishProfile
 */
class PublicParishProfileResource extends JsonResource
{
    use ResolvesMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'history_description' => $this->history_description,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'established_year' => $this->established_year,
            'logo' => $this->whenLoaded('logoMedia', fn () => $this->mediaUrl($this->logoMedia)),
            'logo_alt' => $this->logo_alt,
            'cover' => $this->whenLoaded('coverMedia', fn () => $this->mediaUrl($this->coverMedia, 'large')),
            'cover_alt' => $this->cover_alt,
            'map_url' => $this->map_url,
            'video_url' => $this->video_url,
        ];
    }
}
