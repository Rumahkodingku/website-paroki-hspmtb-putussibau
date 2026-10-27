<?php

namespace App\Http\Controllers\MasterData;

use App\Concerns\PostFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\News\StorePostCategoryRequest;
use App\Http\Requests\News\UpdatePostCategoryRequest;
use App\Http\Resources\News\PostCategoryResource;
use App\Models\PostCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Article categories — /admin/kategori-berita
 *
 * Plain CRUD with one rule that is not plain: a category still holding articles
 * cannot be deleted. Roadmap 5.5 asks for a message rather than a database
 * error, and the RESTRICT foreign key is the layer underneath it rather than the
 * thing the admin meets. Deleting a category does not cascade, so the refusal is
 * the only answer — the alternative would be articles with no bucket.
 *
 * @see docs/DECISIONS.md D-43
 */
class PostCategoryController extends Controller
{
    use PostFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PostCategory::class);

        $search = $request->string('search')->toString();

        $postCategories = PostCategory::query()
            // Before the select, not after: select() below withCount() drops the
            // counts silently and the column reads as zero. See D-41.
            ->withCount('posts')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('post_categories.name', 'like', "%{$search}%"));

        $postCategories = $this->applyAdminSort(
            $postCategories,
            $request,
            [
                'name' => 'post_categories.name',
                'slug' => 'post_categories.slug',
                'sort_order' => 'post_categories.sort_order',
            ],
            [['post_categories.sort_order', 'asc'], ['post_categories.name', 'asc']],
        )->paginate($this->perPage($request))
            ->withQueryString();

        return Inertia::render('admin/master-data/kategori-berita/index', [
            'postCategories' => PostCategoryResource::collection($postCategories),
            ...$this->postFormMeta(),
            'filters' => [
                'search' => $search,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', PostCategory::class);

        return Inertia::render('admin/master-data/kategori-berita/tambah', $this->postFormMeta());
    }

    public function store(StorePostCategoryRequest $request): RedirectResponse
    {
        PostCategory::create($request->postCategoryPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori berita berhasil ditambahkan.')]);

        return to_route('post-categories.index');
    }

    public function edit(PostCategory $postCategory): Response
    {
        Gate::authorize('update', $postCategory);

        return Inertia::render('admin/master-data/kategori-berita/ubah', [
            'postCategory' => PostCategoryResource::make($postCategory)->resolve(),
            ...$this->postFormMeta(),
        ]);
    }

    public function update(UpdatePostCategoryRequest $request, PostCategory $postCategory): RedirectResponse
    {
        $postCategory->update($request->postCategoryPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori berita berhasil diperbarui.')]);

        return to_route('post-categories.index');
    }

    public function destroy(PostCategory $postCategory): RedirectResponse
    {
        Gate::authorize('delete', $postCategory);

        // withTrashed(), and this is the whole reason it is here.
        //
        // posts.post_category_id is ON DELETE RESTRICT, and a soft-deleted
        // article is still a row holding that key. The relation's default scope
        // hides it, so the ordinary count reads zero, the guard lets the delete
        // through, and MySQL answers with a QueryException that surfaces as a
        // 500 — the one outcome worse than the refusal this method exists to
        // produce. Same class of bug as the "Target terhapus" count D-40 records.
        $posts = $postCategory->posts()->withTrashed()->count();

        if ($posts > 0) {
            return to_route('post-categories.index')->withErrors([
                'post_category' => __('Kategori berita tidak dapat dihapus karena masih memiliki :count artikel. Pindahkan artikelnya terlebih dahulu.', [
                    'count' => $posts,
                ]),
            ]);
        }

        $postCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori berita berhasil dihapus.')]);

        return to_route('post-categories.index');
    }
}
