<?php

namespace App\Http\Resources\Public\News;

use App\Http\Resources\MassSchedule\ResolvesNestedResources;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * An article as the public list card needs it.
 *
 * Deliberately narrower than PublicPostDetailResource, and the reason is worth
 * stating because it is the whole point of having two classes: content is HTML
 * and the list does not render it. A card needs a picture, a date, a category, a
 * title and a sentence. The body is not sent, so a page with fifteen cards does
 * not carry fifteen copies of the article in its HTML.
 *
 * @mixin Post
 */
class PublicPostResource extends JsonResource
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
            'excerpt' => $this->excerpt ?? $this->displayDescription(),
            'image' => $this->imageUrl(),
            'image_alt' => $this->image_alt,
            'category' => $this->nested(PublicPostCategoryResource::class, $this->whenLoaded('category')),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    /**
     * The stored path turned into a URL at render time.
     *
     * featured_image holds a path on the variant disk rather than a URL, so the
     * host is never baked into the database and moving the site to a domain of
     * its own changes nothing. See Media::variantPath().
     */
    protected function imageUrl(): ?string
    {
        if ($this->featured_image === null || trim($this->featured_image) === '') {
            return null;
        }

        return Storage::disk((string) config('media.variant_disk'))->url($this->featured_image);
    }
}
