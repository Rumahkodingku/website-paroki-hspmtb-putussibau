<?php

namespace App\Http\Controllers\News;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Public\News\PublicPostDetailResource;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-04: an administrator may read an article before it is published.
 *
 * A separate controller rather than a `preview` flag on the public one, for
 * three reasons that all come down to it being a different question:
 *
 *   - it is behind the admin middleware, and the public controller has none;
 *   - it must reach drafts, scheduled articles and archived ones, which is the
 *     opposite of the scope the public binding uses;
 *   - it renders noindex, because a preview URL that a search engine indexed
 *     would put a draft in results.
 *
 * Reading does not increment view_count. D-12 counts readers, and an admin
 * checking an article five times before publishing it is not five readers.
 *
 * @see docs/PRD.md ADM-04, D-12
 */
class PostPreviewController extends Controller
{
    public function show(Post $post): Response
    {
        // viewAny rather than update: the preview button belongs to anybody who
        // may open the list at all.
        Gate::authorize('viewAny', Post::class);

        return Inertia::render('admin/berita/preview', [
            'post' => PublicPostDetailResource::make($post->load('category'))->resolve(),
            'isPublished' => $post->status === PostStatus::Published,
        ]);
    }
}
