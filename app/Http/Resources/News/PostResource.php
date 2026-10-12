<?php

namespace App\Http\Resources\News;

use App\Http\Resources\MassSchedule\ResolvesNestedResources;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * An article as the admin list and edit form need it.
 *
 * The list and the form read the same row and need nearly the same things, so
 * they share one class. The public shape is deliberately a different class —
 * see App\Http\Resources\Public\News\PublicPostResource — because what may be
 * sent to a visitor and what may be sent to an administrator are different
 * questions, and mixing them is how an internal field ends up in the page
 * source. ARCHITECTURE.md Part C rule 1.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
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
            'post_category_id' => $this->post_category_id,
            'author_id' => $this->author_id,
            'author_name' => $this->displayAuthorName(),
            'excerpt' => $this->excerpt,
            // Sent to the editor as the stored, already-sanitized HTML. It was
            // cleaned on the way in; see PostValidationRules.
            'content' => $this->content,
            'featured_image' => $this->featured_image,
            // The stored path turned into a URL for the picker's preview. The
            // path is kept above because it is what the column holds and what a
            // round trip writes; this is only ever read by the image field, and
            // resolving it here rather than in React keeps the /storage
            // convention (and APP_URL) on the server. See Media::url().
            'featured_image_url' => $this->featuredImageUrl(),
            'image_alt' => $this->image_alt,
            'scripture_reference' => $this->scripture_reference,
            // Enum to value, so React receives the same string the column holds.
            'status' => $this->status->value,
            // ISO 8601, always. React formats it in app.display_timezone, which
            // arrives as the shared prop `displayTimezone`. See D-01.
            'published_at' => $this->published_at?->toIso8601String(),
            'is_pinned' => $this->is_pinned,
            // Read-only here and never a form field: it is written by
            // RecordPostViewAction and by nothing else.
            'view_count' => $this->view_count,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'media_id' => $this->media_id,
            // The option shape, not PostCategoryResource: the article form
            // renders a dropdown and nothing else, and a row carrying a `can`
            // block and an article count would put three fields in the page
            // source that no page reads.
            'category' => $this->nested(PostCategoryOptionResource::class, $this->whenLoaded('category')),
            // What stands in the way of deleting the category, so the dialog can
            // name it before the server refuses.
            'posts_count' => $this->whenCounted('posts'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                // ADM-04: a super admin may read an article before it is
                // published. The button is hidden for anyone else, and the
                // route is behind the same check either way.
                'preview' => $request->user()?->can('viewAny', Post::class) ?? false,
            ],
        ];
    }

    /**
     * The featured image path as a browser URL, or null when there is none.
     *
     * featured_image holds a path on the variant disk, so a value pasted into an
     * <img> resolves relative to whatever admin URL the form is on. Resolving it
     * here is the same thing PublicPostResource does, and it keeps the disk
     * convention out of the frontend.
     */
    private function featuredImageUrl(): ?string
    {
        if ($this->featured_image === null || trim($this->featured_image) === '') {
            return null;
        }

        return Storage::disk((string) config('media.variant_disk'))->url($this->featured_image);
    }
}
