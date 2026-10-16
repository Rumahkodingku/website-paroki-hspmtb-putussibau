<?php

namespace App\Http\Controllers\Komunitas;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Komunitas\PublicCommunityCardResource;
use App\Http\Resources\Public\Komunitas\PublicCommunityCategoryResource;
use App\Http\Resources\Public\Komunitas\PublicCommunityDetailResource;
use App\Models\Community;
use App\Models\CommunityCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public community pages — /komunitas and /komunitas/{slug}
 *
 * Every query here starts from Community::published(). That is the whole of
 * roadmap 5.2.2, and it is a scope rather than a repeated condition because the
 * same filter has to hold in the list, the detail and the route binding — and a
 * second definition of "public" is how one of them ends up not having it.
 *
 * Slug binding is `{community:slug}`, which Community::resolveRouteBinding
 * resolves through that same scope. A draft therefore answers 404 by not being
 * found, with no `abort_if` in this class that a later edit could forget.
 *
 * Communities no longer filter by area: the community↔area relation was removed
 * in D-57 and Area is now its own module.
 *
 * @see docs/DECISIONS.md D-55, D-57
 */
class PublicCommunityController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('q')->toString();
        $categorySlug = $request->string('kategori')->toString();

        $category = $this->filteredCategory($categorySlug);

        return Inertia::render('public/komunitas', [
            'communities' => PublicCommunityCardResource::collection(
                $this->filteredCommunities($search, $category)->paginate(12)->withQueryString()
            ),
            'categories' => array_values(
                PublicCommunityCategoryResource::collection($this->categories())->resolve()
            ),
            'filters' => [
                'q' => $search,
                'kategori' => $category instanceof CommunityCategory ? $category->slug : '',
            ],
        ]);
    }

    public function show(Community $community): Response
    {
        $community->load([
            'communityCategory',
            'logoMedia.variants',
            'thumbnailMedia.variants',
        ]);

        return Inertia::render('public/komunitas/show', [
            'community' => PublicCommunityDetailResource::make($community)->resolve(),
        ]);
    }

    /**
     * The paginated list, already restricted to published communities.
     *
     * @return Builder<Community>
     */
    private function filteredCommunities(string $search, ?CommunityCategory $category): Builder
    {
        return Community::query()
            ->published()
            ->with(['communityCategory', 'thumbnailMedia.variants'])
            ->when($search !== '', fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%"))
            ->when($category !== null, fn (Builder $query) => $query
                ->where('community_category_id', $category->getKey()))
            ->ordered();
    }

    /**
     * Categories that actually hold a published community.
     *
     * @return Collection<int, CommunityCategory>
     */
    private function categories(): Collection
    {
        return CommunityCategory::query()
            ->whereHas('communities', fn (Builder $query) => $query->published())
            ->ordered()
            ->get();
    }

    /**
     * A slug nobody has is no filter rather than an empty result, so a mangled
     * link shows the directory instead of a broken page.
     */
    private function filteredCategory(string $slug): ?CommunityCategory
    {
        if ($slug === '') {
            return null;
        }

        return CommunityCategory::query()->where('slug', $slug)->first();
    }
}
