<?php

namespace App\Http\Resources\Komunitas;

use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A community as the edit form needs it: every field, plus its category.
 *
 * The image urls are resolved from the Media rows, so the controller eager loads
 * `logoMedia.variants` and `thumbnailMedia.variants`.
 *
 * @mixin Community
 */
class CommunityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'community_category_id' => $this->community_category_id,
            'logo_media_id' => $this->logo_media_id,
            'logo_alt' => $this->logo_alt,
            'logo_url' => $this->whenLoaded('logoMedia', fn (): ?string => $this->logoMedia->url('medium')),
            'thumbnail_media_id' => $this->thumbnail_media_id,
            'thumbnail_alt' => $this->thumbnail_alt,
            'thumbnail_url' => $this->whenLoaded('thumbnailMedia', fn (): ?string => $this->thumbnailMedia->url('medium')),
            'description' => $this->description,
            'vision' => $this->vision,
            'mission' => $this->mission,
            'meeting_schedule' => $this->meeting_schedule,
            'meeting_location' => $this->meeting_location,
            'eligibility' => $this->eligibility,
            'joining_info' => $this->joining_info,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'show_contact' => $this->show_contact,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'tiktok' => $this->tiktok,
            'youtube' => $this->youtube,
            'website' => $this->website,
            'status' => $this->status->value,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'category' => $this->whenLoaded('communityCategory', fn (): array => [
                'id' => $this->communityCategory->getKey(),
                'name' => $this->communityCategory->name,
            ]),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
