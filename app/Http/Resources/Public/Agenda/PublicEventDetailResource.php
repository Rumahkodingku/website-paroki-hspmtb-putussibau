<?php

namespace App\Http\Resources\Public\Agenda;

use App\Http\Resources\Public\News\PublicPostResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * An activity as the public detail page needs it.
 *
 * Everything the calendar needs to offer the two "add it to my calendar"
 * actions is resolved here rather than in React: a UTC start and end, a
 * one-line description, and a location. A link built from those in the browser
 * is a link whose encoding is the browser's opinion, and AC-EVT-3 is about the
 * link working.
 *
 * @mixin Event
 */
class PublicEventDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'plain_description' => $this->plainDescription(),
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'is_all_day' => $this->is_all_day,
            'location_name' => $this->displayLocationName(),
            'location_url' => $this->displayMapsUrl(),
            'poster' => $this->posterUrl(),
            'poster_alt' => $this->poster_alt,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->getKey(),
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'color_value' => $this->category->colorValue(),
            ]),
        ];
    }

    /**
     * @see PublicPostResource::imageUrl()
     */
    protected function posterUrl(): ?string
    {
        if ($this->poster_path === null || trim($this->poster_path) === '') {
            return null;
        }

        return Storage::disk((string) config('media.variant_disk'))->url($this->poster_path);
    }
}
