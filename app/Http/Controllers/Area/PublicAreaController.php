<?php

namespace App\Http\Controllers\Area;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Area\PublicAreaCardResource;
use App\Http\Resources\Public\Area\PublicAreaDetailResource;
use App\Models\Area;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public Area pages — /area and its detail.
 *
 * Every query starts from Area::active(), and the detail binds `{area:slug}`
 * through Area::resolveRouteBinding on that same scope, so an inactive area
 * answers 404 rather than appearing. There is no pagination: an area list is
 * small and a directory grouped by type reads better in one page.
 *
 * @see docs/DECISIONS.md D-57
 */
class PublicAreaController extends Controller
{
    /**
     * Home languages in the order the sections render.
     *
     * @var list<string>
     */
    private const SECTION_ORDER = ['stasi', 'lingkungan', 'wilayah'];

    public function index(): Response
    {
        $areas = Area::query()
            ->active()
            ->with('parent')
            ->ordered()
            ->get();

        return Inertia::render('public/profil/area/index', [
            'sections' => $this->sections($areas),
            'hasAreas' => $areas->isNotEmpty(),
        ]);
    }

    public function show(Area $area): Response
    {
        $area->load([
            'parent',
            'children' => fn ($query) => $query->active()->ordered(),
        ]);

        return Inertia::render('public/profil/area/show', [
            'area' => PublicAreaDetailResource::make($area)->resolve(),
        ]);
    }

    /**
     * The active areas, grouped by type into the sections the page renders.
     *
     * @param  Collection<int, Area>  $areas
     * @return list<array<string, mixed>>
     */
    private function sections(Collection $areas): array
    {
        $sections = [];

        foreach (self::SECTION_ORDER as $type) {
            $group = $areas
                ->filter(fn (Area $area): bool => $area->type->value === $type)
                ->values();

            if ($group->isEmpty()) {
                continue;
            }

            $sections[] = [
                'type' => $type,
                'label' => (string) __('area.public.section_'.$type),
                'areas' => array_values(PublicAreaCardResource::collection($group)->resolve()),
            ];
        }

        return $sections;
    }
}
