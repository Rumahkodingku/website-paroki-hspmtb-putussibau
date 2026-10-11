<?php

namespace App\Http\Controllers\News;

use App\Actions\News\RecordPostViewAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\MassSchedule\ResolvesNestedResources;
use App\Http\Resources\Public\News\PublicPostCategoryResource;
use App\Http\Resources\Public\News\PublicPostDetailResource;
use App\Http\Resources\Public\News\PublicPostResource;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public news pages — /berita and /berita/{slug}
 *
 * Every query here starts from Post::published(). That is the whole of POST-02
 * and POST-09, and it is a scope rather than three repeated conditions because
 * the same filter has to hold in five places — the list, the detail, the pinned
 * strip, related posts and the route binding — and a second definition of
 * "public" is how one of them ends up not having it.
 *
 * Slug binding is `{post:slug}`, which Post::resolveRouteBinding resolves
 * through that same scope. A draft therefore answers 404 by not being found, with
 * no `abort_if` in this class that a later edit could forget.
 *
 * @see docs/DECISIONS.md D-39, D-43
 */
class PublicPostController extends Controller
{
    use ResolvesNestedResources;

    /**
     * POST-07. Two, and capped in the query as well as in the form request.
     */
    private const PINNED_LIMIT = 2;

    public function index(Request $request): Response
    {
        $search = $request->string('q')->toString();
        $categorySlug = $request->string('kategori')->toString();

        $category = $this->filteredCategory($categorySlug);

        return Inertia::render('public/berita', [
            'pinned' => $this->pinned(),
            // The category filter is applied through the resolved category, not
            // through the raw slug: a slug nobody has 404s into no filter rather
            // than into an empty list that looks like a broken page. See
            // PublicMassScheduleController::filteredDay() for the same shape.
            'posts' => PublicPostResource::collection(
                $this->filteredPosts($search, $category)->paginate(9)->withQueryString()
            ),
            'categories' => array_values(
                PublicPostCategoryResource::collection($this->categories())->resolve()
            ),
            'filters' => [
                'q' => $search,
                'kategori' => $category instanceof PostCategory ? $category->slug : '',
            ],
        ]);
    }

    public function show(Request $request, Post $post, RecordPostViewAction $recordView): Response
    {
        $this->recordViewOnce($request, $post, $recordView);

        return Inertia::render('public/berita/show', [
            'post' => PublicPostDetailResource::make($post->load('category'))->resolve(),
            'related' => $this->related($post),
            'adjacent' => $this->adjacent($post),
        ]);
    }

    /**
     * D-12: at most one count per session per article, and never for staff.
     *
     * The mark is written with `put` and read with `has`, and the key has to
     * stay in the session for the whole session. `pull` looks tidier — it reads
     * and clears in one call — and it is wrong: `pull` removes the key it
     * returns, so the *next* visit finds nothing to read and counts again. The
     * sequence is count, skip, count, skip, and a reader refreshing five times
     * leaves two. ViewCountTest pins this by reading the same article three
     * times in one session.
     *
     * A signed-in account is not counted at all, whatever it reads. Every
     * request to the preview passes through here, and so does every editor who
     * opens their own article to check it before publishing — a count that
     * includes the parish staff stops measuring the parish's readership. It
     * also subsumes the preview, which would otherwise need its own exclusion
     * here and would need it again the next time a screen previews a post.
     *
     * The session is a cookie here, not a server-side store, so "once per
     * session" means once per browser until the cookie is cleared. That is the
     * honest reading of D-12 on an application with no accounts for visitors.
     */
    private function recordViewOnce(
        Request $request,
        Post $post,
        RecordPostViewAction $recordView,
    ): void {
        if ($request->user() !== null) {
            return;
        }

        $key = 'viewed-post.'.$post->getKey();

        if ($request->session()->has($key)) {
            return;
        }

        $request->session()->put($key, true);

        $recordView->handle($post, $request->userAgent() ?? '');
    }

    /**
     * The featured strip.
     *
     * A separate query from the list on purpose. If the two shared one, the two
     * pinned articles would occupy two of the nine slots on page one and the
     * list below would begin with the third article — which reads as though the
     * newest two had gone missing.
     *
     * @return list<array<string, mixed>>
     */
    private function pinned(): array
    {
        return array_values(PublicPostResource::collection(
            Post::query()
                ->pinned()
                ->with('category')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(self::PINNED_LIMIT)
                ->get()
        )->resolve());
    }

    /**
     * The paginated list, already restricted to published articles.
     *
     * @return Builder<Post>
     */
    private function filteredPosts(string $search, ?PostCategory $category): Builder
    {
        return Post::query()
            ->published()
            ->with('category')
            ->when($search !== '', fn (Builder $query) => $query
                ->where(function (Builder $inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                }))
            ->when($category !== null, fn (Builder $query) => $query
                ->where('post_category_id', $category->getKey()))
            // id last, because published_at is not unique: two articles published
            // in the same second would otherwise come back in whatever order
            // MySQL felt like, and page two could repeat a row from page one.
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * Other articles in the same category (PRD 7.4).
     *
     * Scoped to published(), so a draft can never surface here — the leak that
     * a `where('category_id', …)` without the scope would open.
     *
     * @return list<array<string, mixed>>
     */
    private function related(Post $post): array
    {
        return array_values(PublicPostResource::collection(
            Post::query()
                ->published()
                ->where('post_category_id', $post->post_category_id)
                ->whereKeyNot($post->getKey())
                ->with('category')
                ->orderByDesc('published_at')
                ->limit(3)
                ->get()
        )->resolve());
    }

    /**
     * The previous and next article by publication date.
     *
     * An optional extra in PRD 7.4, and it is here because a reader who reaches
     * the end of an article wants to keep reading rather than go back to a list.
     * Both sides come out of the published scope for the same reason related
     * does.
     *
     * @return array{previous: array<string, mixed>|null, next: array<string, mixed>|null}
     */
    private function adjacent(Post $post): array
    {
        $older = Post::query()
            ->published()
            ->where(fn (Builder $query) => $query
                ->where('published_at', '<', $post->published_at)
                ->orWhere(function (Builder $tie) use ($post): void {
                    $tie->where('published_at', $post->published_at)
                        ->whereKeyNot($post->getKey())
                        ->where('id', '<', $post->getKey());
                }))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        $newer = Post::query()
            ->published()
            ->where(fn (Builder $query) => $query
                ->where('published_at', '>', $post->published_at)
                ->orWhere(function (Builder $tie) use ($post): void {
                    $tie->where('published_at', $post->published_at)
                        ->whereKeyNot($post->getKey())
                        ->where('id', '>', $post->getKey());
                }))
            ->orderBy('published_at')
            ->orderBy('id')
            ->first();

        return [
            'previous' => $older === null
                ? null
                : PublicPostResource::make($older->load('category'))->resolve(),
            'next' => $newer === null
                ? null
                : PublicPostResource::make($newer->load('category'))->resolve(),
        ];
    }

    /**
     * @return Collection<int, PostCategory>
     */
    private function categories(): Collection
    {
        return PostCategory::query()
            ->whereHas('posts', fn (Builder $query) => $query->published())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * A slug nobody has, or one whose category holds nothing published, is no
     * filter rather than an empty result.
     */
    private function filteredCategory(string $slug): ?PostCategory
    {
        if ($slug === '') {
            return null;
        }

        return PostCategory::query()->where('slug', $slug)->first();
    }
}
