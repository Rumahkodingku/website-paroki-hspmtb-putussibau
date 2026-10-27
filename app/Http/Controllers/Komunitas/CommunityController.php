<?php

namespace App\Http\Controllers\Komunitas;

use App\Actions\Komunitas\DeleteCommunityAction;
use App\Actions\Komunitas\SaveCommunityAction;
use App\Concerns\CommunityFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Komunitas\StoreCommunityRequest;
use App\Http\Requests\Komunitas\UpdateCommunityRequest;
use App\Http\Resources\Komunitas\CommunityCategoryOptionResource;
use App\Http\Resources\Komunitas\CommunityListResource;
use App\Http\Resources\Komunitas\CommunityResource;
use App\Models\Community;
use App\Models\CommunityCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Komunitas — the admin list and form.
 *
 * The list is server-paginated and server-sorted (SortsAdminList), the same shape
 * as Pelayanan. The write path goes through SaveCommunityAction because a save
 * derives published_at, which is more than a controller method should own.
 *
 * @see docs/DECISIONS.md D-55
 */
class CommunityController extends Controller
{
    use CommunityFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Community::class);

        $search = $request->string('search')->toString();
        $category = $request->string('kategori')->toString();
        $status = $request->string('status')->toString();
        $publication = $request->string('publikasi')->toString();

        $communities = Community::query()
            ->with(['communityCategory', 'thumbnailMedia.variants'])
            ->when($search !== '', fn (Builder $query) => $query
                ->where('communities.name', 'like', "%{$search}%"))
            ->when(
                $category !== '' && ctype_digit($category),
                fn (Builder $query) => $query->where('communities.community_category_id', (int) $category)
            )
            ->when(
                in_array($status, ['forming', 'active', 'inactive'], true),
                fn (Builder $query) => $query->where('communities.status', $status)
            )
            ->when(
                in_array($publication, ['published', 'draft'], true),
                fn (Builder $query) => $query->where('communities.is_published', $publication === 'published')
            );

        $communities = $this->applyAdminSort(
            $communities,
            $request,
            [
                'name' => 'communities.name',
                'status' => 'communities.status',
                'is_published' => 'communities.is_published',
                'updated_at' => 'communities.updated_at',
            ],
            [['communities.name', 'asc']],
        )->paginate($this->perPage($request))->withQueryString();

        return Inertia::render('admin/komunitas', [
            'communities' => CommunityListResource::collection($communities),
            'categories' => $this->categoryOptions(),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'status' => $status,
                'publication' => $publication,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->communityFormMeta(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Community::class);

        return Inertia::render('admin/komunitas/tambah', [
            'categories' => $this->categoryOptions(),
            ...$this->communityFormMeta(),
        ]);
    }

    public function store(StoreCommunityRequest $request, SaveCommunityAction $action): RedirectResponse
    {
        $community = new Community;
        $action->handle($community, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('komunitas.flash.community_created')]);

        return to_route('communities.edit', $community);
    }

    public function edit(Community $community): Response
    {
        Gate::authorize('update', $community);

        $community->load([
            'communityCategory',
            'logoMedia.variants',
            'thumbnailMedia.variants',
        ]);

        return Inertia::render('admin/komunitas/ubah', [
            'community' => CommunityResource::make($community)->resolve(),
            'categories' => $this->categoryOptions($community),
            ...$this->communityFormMeta(),
        ]);
    }

    public function update(UpdateCommunityRequest $request, Community $community, SaveCommunityAction $action): RedirectResponse
    {
        $action->handle($community, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('komunitas.flash.community_updated')]);

        return to_route('communities.edit', $community);
    }

    public function destroy(Community $community, DeleteCommunityAction $action): RedirectResponse
    {
        Gate::authorize('delete', $community);

        $action->handle($community);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('komunitas.flash.community_deleted')]);

        return to_route('communities.index');
    }

    /**
     * The category dropdown: active categories, plus the one a community already
     * sits in even if it has since been deactivated, so editing that community
     * does not silently move it (roadmap 5.1).
     *
     * @return list<array<string, mixed>>
     */
    private function categoryOptions(?Community $current = null): array
    {
        $categories = CommunityCategory::query()->where('is_active', true);

        if ($current !== null) {
            $categories->orWhere('id', $current->community_category_id);
        }

        return array_values(
            CommunityCategoryOptionResource::collection($categories->ordered()->get())->resolve()
        );
    }
}
