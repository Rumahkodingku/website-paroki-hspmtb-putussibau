<?php

namespace App\Http\Controllers\News;

use App\Actions\News\SavePostAction;
use App\Concerns\PostFormMeta;
use App\Concerns\SortsAdminList;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\News\StorePostRequest;
use App\Http\Requests\News\UpdatePostRequest;
use App\Http\Resources\News\PostCategoryOptionResource;
use App\Http\Resources\News\PostResource;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Articles — /admin/berita
 *
 * A list, a form and a soft delete. The interesting parts are all in the
 * request and the action; what is left here is orchestration, which is what a
 * controller is for (ARCHITECTURE.md Part A section 2).
 *
 * The list route is named `.list` and the module root `/admin/berita` is a
 * redirect to it, which is the shape Phase 04 settled on for Jadwal Misa: PRD
 * 5.2 names the root, and every breadcrumb in the module points at it, but a
 * redirect is not a page. See D-42.
 *
 * @see docs/DECISIONS.md D-41, D-42, D-43
 */
class PostController extends Controller
{
    use PostFormMeta;

    public function __construct(private readonly AuditLogger $audit) {}

    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $categoryId = $request->string('kategori')->toString();

        $posts = Post::query()
            // Eager loaded because the list renders the category name on every
            // row, and shouldBeStrict() outside production turns a lazy load
            // into an exception rather than an N+1 nobody notices.
            ->with('category')
            // The byline falls back to the account's name when an admin left
            // author_name blank, so the relation is genuinely read here — and
            // on the rare row that needs it, a lazy load would be a 500 rather
            // than an N+1.
            ->with('author')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('posts.title', 'like', "%{$search}%"))
            ->when(
                in_array($status, array_column(PostStatus::cases(), 'value'), true),
                fn (Builder $query) => $query->where('posts.status', $status)
            )
            ->when($categoryId !== '', fn (Builder $query) => $query
                ->where('posts.post_category_id', $categoryId));

        $posts = $this->applyAdminSort(
            $posts,
            $request,
            [
                'title' => 'posts.title',
                'status' => 'posts.status',
                'published_at' => 'posts.published_at',
                'created_at' => 'posts.created_at',
                'is_pinned' => 'posts.is_pinned',
            ],
            [['posts.created_at', 'desc']],
        )->paginate($this->perPage($request))
            ->withQueryString();

        return Inertia::render('admin/berita', [
            'posts' => PostResource::collection($posts),
            ...$this->postFormMeta(),
            'postCategories' => array_values(
                PostCategoryOptionResource::collection($this->categories())->resolve()
            ),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'kategori' => $categoryId,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Post::class);

        return Inertia::render('admin/berita/tambah', [
            ...$this->postFormMeta(),
            'postCategories' => array_values(
                PostCategoryOptionResource::collection($this->categories())->resolve()
            ),
        ]);
    }

    public function store(StorePostRequest $request, SavePostAction $action): RedirectResponse
    {
        $post = $action->handle(new Post, $request->postPayload(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Artikel berhasil disimpan.')]);

        return to_route('posts.edit', $post);
    }

    public function edit(Post $post): Response
    {
        Gate::authorize('update', $post);

        return Inertia::render('admin/berita/ubah', [
            'post' => PostResource::make($post->load(['category', 'author']))->resolve(),
            ...$this->postFormMeta(),
            'postCategories' => array_values(
                PostCategoryOptionResource::collection($this->categories())->resolve()
            ),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post, SavePostAction $action): RedirectResponse
    {
        $action->handle($post, $request->postPayload(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Artikel berhasil diperbarui.')]);

        return to_route('posts.edit', $post);
    }

    /**
     * A soft delete, not a removal (roadmap 5.5).
     *
     * The article keeps its row and its slug; what stops it appearing is the
     * SoftDeletes scope on Post::published(), which every public query in this
     * module goes through. There is no restore endpoint and no force delete —
     * see D-45 for why the slug cannot be reused either.
     */
    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        DB::transaction(function () use ($post): void {
            $before = $this->audit->snapshot($post, 'news');
            $post->delete();
            $this->audit->deleted('news', $post, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Artikel berhasil dihapus.')]);

        return to_route('posts.list');
    }

    /**
     * The category dropdown, in the parish's order.
     *
     * A plain array rather than a resource collection handed over unresolved:
     * see D-39. Inertia wraps a non-paginated collection as {"data": [...]},
     * React then calls .map on an object, and the page renders blank while every
     * test is green.
     *
     * @return Collection<int, PostCategory>
     */
    private function categories(): Collection
    {
        return PostCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
