<?php

namespace App\Http\Resources\Public\Livestream;

use App\Models\Livestream;
use App\Support\YoutubeEmbed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A livestream as a visitor may see it.
 *
 * Only the public metadata and the three URLs derived from the stored id are
 * sent. `youtube_url` (the admin's source link), creator columns, live_slot and
 * soft-delete state never reach the browser.
 *
 * @mixin Livestream
 */
class PublicLivestreamResource extends JsonResource
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
            'video_id' => $this->youtube_video_id,
            'embed_url' => YoutubeEmbed::embedUrl($this->youtube_video_id),
            'watch_url' => YoutubeEmbed::watchUrl($this->youtube_video_id),
            'thumbnail_url' => YoutubeEmbed::thumbnailUrl($this->youtube_video_id),
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'location' => $this->location?->name,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'recorded_at' => $this->recorded_at?->toIso8601String(),
        ];
    }
}
