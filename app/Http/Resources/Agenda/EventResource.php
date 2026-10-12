<?php

namespace App\Http\Resources\Agenda;

use App\Http\Resources\MassSchedule\LocationOptionResource;
use App\Http\Resources\MassSchedule\ResolvesNestedResources;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * An activity as the admin list and edit form need it.
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
    use ResolvesNestedResources;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'event_category_id' => $this->event_category_id,
            'description' => $this->description,
            // ISO 8601 in UTC; React renders it in the shared displayTimezone.
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'is_all_day' => $this->is_all_day,
            'location_id' => $this->location_id,
            'location_name' => $this->displayLocationName(),
            'poster_path' => $this->poster_path,
            // Path stays the stored contract; the URL is what the image picker's
            // preview reads. Resolving it here mirrors PublicEventResource and
            // keeps the /storage convention on the server.
            'poster_url' => $this->posterUrl(),
            'poster_alt' => $this->poster_alt,
            'status' => $this->status->value,
            'is_published' => $this->is_published,
            'media_id' => $this->media_id,
            'category' => $this->nested(EventCategoryOptionResource::class, $this->whenLoaded('category')),
            'location' => $this->nested(LocationOptionResource::class, $this->whenLoaded('location')),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                // The duplicate action writes a row, so it is gated on update
                // rather than having an ability of its own. Roadmap 9 lists it
                // as an endpoint, not as a separate privilege.
                'duplicate' => $request->user()?->can('update', $this->resource) ?? false,
            ],
        ];
    }

    /**
     * The poster path as a browser URL, or null when there is none.
     *
     * poster_path holds a path on the variant disk, so a value pasted into an
     * <img> would resolve relative to the admin URL the form is on. Resolving it
     * here mirrors PublicEventResource.
     */
    private function posterUrl(): ?string
    {
        if ($this->poster_path === null || trim($this->poster_path) === '') {
            return null;
        }

        return Storage::disk((string) config('media.variant_disk'))->url($this->poster_path);
    }
}
