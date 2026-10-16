<?php

namespace App\Http\Resources\Komunitas;

use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A community as the admin list needs it: enough for the row, not the profile.
 *
 * `thumbnail_url` is resolved from the Media row, so the controller must eager
 * load `thumbnailMedia.variants` or strict mode throws.
 *
 * @mixin Community
 */
class CommunityListResource extends JsonResource
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
            'thumbnail_url' => $this->whenLoaded('thumbnailMedia', fn (): ?string => $this->thumbnailMedia->url('medium')),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
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
