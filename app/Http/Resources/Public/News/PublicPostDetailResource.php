<?php

namespace App\Http\Resources\Public\News;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * An article as the public detail page needs it.
 *
 * This is the only place content is sent to a visitor, and it is safe to do so
 * because the HTML was cleaned by HtmlSanitizer on the way into storage — the
 * invariant components/rich-text.tsx documents, and the reason the sanitizer
 * running in the request layer (not the editor) is load-bearing.
 *
 * The SEO fields are resolved here rather than in React. POST-10 requires Open
 * Graph title, description and image on this page, and a fallback chain written
 * in TypeScript is a chain that a future page can quietly implement
 * differently.
 *
 * @mixin Post
 */
class PublicPostDetailResource extends JsonResource
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
            // The byline the parish chose, or the account's name at the time it
            // was written. Resolved by the model so D-21's "never send the raw
            // User" is not quietly broken by a convenience cast.
            'author_name' => $this->displayAuthorName(),
            // Sanitized HTML. Only rendered through RichText.
            'content' => $this->content,
            'image' => $this->imageUrl(),
            'image_alt' => $this->image_alt,
            'scripture_reference' => $this->scripture_reference,
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->getKey(),
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'published_at' => $this->published_at?->toIso8601String(),
            'view_count' => $this->view_count,
            'seo' => [
                'title' => $this->displayMetaTitle(),
                // meta_description, then excerpt, then the opening of the body.
                'description' => $this->displayDescription(),
            ],
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
