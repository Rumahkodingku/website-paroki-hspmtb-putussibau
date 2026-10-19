<?php

namespace App\Http\Resources\Dashboard;

use App\Http\Resources\Public\News\PublicPostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * An article as the dashboard's Berita Terbaru panel needs it.
 *
 * Narrower than the public card on purpose: the dashboard shows a title, a
 * status, a date and a thumbnail, and nothing else. The body is never sent, so a
 * panel of three articles does not carry three articles in the page source.
 *
 * The image is resolved from featured_image — the same variant path the public
 * card renders — rather than from the media relation, so the two surfaces cannot
 * disagree about which picture belongs to an article.
 *
 * @mixin Post
 */
class RecentPostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'image' => $this->imageUrl(),
            'image_alt' => $this->image_alt,
        ];
    }

    /**
     * @see PublicPostResource::imageUrl()
     */
    protected function imageUrl(): ?string
    {
        if ($this->featured_image === null || trim($this->featured_image) === '') {
            return null;
        }

        return Storage::disk((string) config('media.variant_disk'))->url($this->featured_image);
    }
}
