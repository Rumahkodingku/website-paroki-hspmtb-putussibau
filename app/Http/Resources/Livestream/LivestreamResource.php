<?php

namespace App\Http\Resources\Livestream;

use App\Models\Livestream;
use App\Support\YoutubeEmbed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A livestream as the admin list and form need it.
 *
 * The thumbnail is derived from the stored video id; nothing in this resource
 * accepts or exposes an iframe/embed URL from admin input.
 *
 * @mixin Livestream
 */
class LivestreamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'youtube_url' => $this->youtube_url,
            'youtube_video_id' => $this->youtube_video_id,
            'thumbnail_url' => YoutubeEmbed::thumbnailUrl($this->youtube_video_id),
            'location_id' => $this->location_id,
            'location' => $this->location?->name,
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'broadcast_status' => $this->broadcast_status->value,
            'broadcast_status_label' => $this->broadcast_status->label(),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'is_recording_available' => $this->is_recording_available,
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
