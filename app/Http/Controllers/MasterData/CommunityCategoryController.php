<?php

namespace App\Http\Controllers\MasterData;

use App\Concerns\CommunityFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Komunitas\StoreCommunityCategoryRequest;
use App\Http\Requests\Komunitas\UpdateCommunityCategoryRequest;
use App\Http\Resources\Komunitas\CommunityCategoryResource;
use App\Models\CommunityCategory;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kategori Komunitas — the master categories, managed from Master Data (D-55
 * point 5). Follows ServiceCategoryController: search/status filter, server
 * sort, and a delete that is refused while a community still points here.
 */
class CommunityCategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    use CommunityFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CommunityCategory::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $categories = CommunityCategory::query()
            ->withCount('communities')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%"))
            ->when(
                in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('is_active', $status === 'active')
            );

        $categories = $this->applyAdminSort(
            $categories,
            $request,
            [
                'name' => 'community_categories.name',
                'sort_order' => 'community_categories.sort_order',
                'is_active' => 'community_categories.is_active',
            ],
            [['community_categories.sort_order', 'asc']],
        )->paginate($this->perPage($request))->withQueryString();

        return Inertia::render('admin/master-data/kategori-komunitas/index', [
            'categories' => CommunityCategoryResource::collection($categories),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->communityFormMeta(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', CommunityCategory::class);

        return Inertia::render('admin/master-data/kategori-komunitas/tambah', [
            ...$this->communityFormMeta(),
        ]);
    }

    public function store(StoreCommunityCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $communityCategory = CommunityCategory::query()->create($request->validated());
            $this->audit->created('community_categories', $communityCategory);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('komunitas.flash.category_created')]);

        return to_route('community-categories.index');
    }

    public function edit(CommunityCategory $communityCategory): Response
    {
        Gate::authorize('update', $communityCategory);

        return Inertia::render('admin/master-data/kategori-komunitas/ubah', [
            'category' => CommunityCategoryResource::make($communityCategory)->resolve(),
            ...$this->communityFormMeta(),
        ]);
    }

    public function update(UpdateCommunityCategoryRequest $request, CommunityCategory $communityCategory): RedirectResponse
    {
        DB::transaction(function () use ($request, $communityCategory): void {
            $before = $this->audit->snapshot($communityCategory, 'community_categories');
            $wasActive = $communityCategory->is_active;

            $communityCategory->update($request->validated());

            $action = $wasActive === $communityCategory->is_active
                ? 'updated'
                : ($communityCategory->is_active ? 'activated' : 'deactivated');

            $this->audit->updated('community_categories', $communityCategory, $before, $action);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('komunitas.flash.category_updated')]);

        return to_route('community-categories.index');
    }

    public function destroy(CommunityCategory $communityCategory): RedirectResponse
    {
        Gate::authorize('delete', $communityCategory);

        // withTrashed: a soft-deleted community still points here through a
        // RESTRICT foreign key, so the category cannot be removed either way.
        $communities = $communityCategory->communities()->withTrashed()->count();

        if ($communities > 0) {
            return to_route('community-categories.index')->withErrors([
                'category' => __('Kategori tidak dapat dihapus karena masih dipakai :count komunitas. Pindahkan komunitas tersebut atau nonaktifkan kategorinya.', [
                    'count' => $communities,
                ]),
            ]);
        }

        DB::transaction(function () use ($communityCategory): void {
            $before = $this->audit->snapshot($communityCategory, 'community_categories');
            $communityCategory->delete();
            $this->audit->deleted('community_categories', $communityCategory, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('komunitas.flash.category_deleted')]);

        return to_route('community-categories.index');
    }
}
