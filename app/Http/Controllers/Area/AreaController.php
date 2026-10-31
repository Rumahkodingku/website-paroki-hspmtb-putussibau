<?php

namespace App\Http\Controllers\Area;

use App\Concerns\AreaFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Http\Resources\Area\AreaOptionResource;
use App\Http\Resources\Area\AreaResource;
use App\Models\Area;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Area — the area module, on its own (D-57).
 *
 * It is not part of Komunitas and not Master Data: areas are operational data
 * with their own public pages. The list is one sortable table of every area —
 * not three sections per type — with a child marked by a rule in its name cell
 * so the hierarchy still reads; there is no pagination because an area list is
 * small and a child split across a page boundary would lose its parent. The
 * hierarchy rules themselves live in AreaValidationRules, not here.
 *
 * @see docs/DECISIONS.md D-57
 */
class AreaController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    use AreaFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Area::class);

        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        $status = $request->string('status')->toString();

        $areas = Area::query()
            ->with(['parent', 'photoMedia'])
            ->when($search !== '', fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%"))
            ->when(
                in_array($type, ['wilayah', 'lingkungan', 'stasi'], true),
                fn (Builder $query) => $query->where('type', $type)
            )
            ->when(
                in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('is_active', $status === 'active')
            );

        /*
         * One sortable table rather than three grouped sections, so the sort
         * whitelist has to be a plain column list. "Wilayah Induk" is absent on
         * purpose: sorting by it needs a join, and a list this size does not
         * earn one.
         *
         * The default order groups by type the way the three sections used to,
         * then honours the admin's own "Urutan" numbers, then name. It is an
         * argument here and not an orderBy() in the chain above: two ORDER BY
         * instructions for one column make MySQL keep both in order, so a
         * chained default silently outranks the sort (D-41).
         */
        $areas = $this->applyAdminSort(
            $areas,
            $request,
            [
                'name' => 'areas.name',
                'type' => 'areas.type',
                'is_active' => 'areas.is_active',
                'sort_order' => 'areas.sort_order',
            ],
            [
                ['areas.type', 'asc'],
                ['areas.sort_order', 'asc'],
                ['areas.name', 'asc'],
            ],
        )->get();

        return Inertia::render('admin/area', [
            // A non-paginated Resource::collection serialises to {data: [...]}
            // (D-39), so it is resolved to a plain list.
            'areas' => array_values(AreaResource::collection($areas)->resolve()),
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->areaFormMeta(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Area::class);

        return Inertia::render('admin/area/tambah', [
            'parents' => $this->parentOptions(),
            ...$this->areaFormMeta(),
        ]);
    }

    public function store(StoreAreaRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $area = Area::query()->create($request->validated());
            $this->audit->created('areas', $area);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('area.flash.area_created')]);

        return to_route('areas.index');
    }

    public function edit(Area $area): Response
    {
        Gate::authorize('update', $area);

        return Inertia::render('admin/area/ubah', [
            'area' => AreaResource::make($area->load(['parent', 'photoMedia']))->resolve(),
            'parents' => $this->parentOptions($area),
            ...$this->areaFormMeta(),
        ]);
    }

    public function update(UpdateAreaRequest $request, Area $area): RedirectResponse
    {
        DB::transaction(function () use ($request, $area): void {
            $before = $this->audit->snapshot($area, 'areas');
            $wasActive = $area->is_active;

            $area->update($request->validated());

            $action = $wasActive === $area->is_active
                ? 'updated'
                : ($area->is_active ? 'activated' : 'deactivated');

            $this->audit->updated('areas', $area, $before, $action);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('area.flash.area_updated')]);

        return to_route('areas.index');
    }

    public function destroy(Area $area): RedirectResponse
    {
        Gate::authorize('delete', $area);

        if ($area->children()->exists()) {
            return to_route('areas.index')->withErrors([
                'area' => __('Wilayah ini masih memiliki lingkungan. Pindahkan atau hapus lingkungan tersebut terlebih dahulu.'),
            ]);
        }

        DB::transaction(function () use ($area): void {
            $before = $this->audit->snapshot($area, 'areas');
            $area->delete();
            $this->audit->deleted('areas', $area, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('area.flash.area_deleted')]);

        return to_route('areas.index');
    }

    /**
     * The parent dropdown for a lingkungan: active wilayah only, plus the one an
     * area already sits under even if it has since been deactivated, so editing
     * the area does not force a move. Optional — a lingkungan may have no parent.
     *
     * @return list<array<string, mixed>>
     */
    private function parentOptions(?Area $current = null): array
    {
        $parents = Area::query()
            ->where('type', 'wilayah')
            ->where('is_active', true);

        if ($current !== null && $current->parent_id !== null) {
            $parents->orWhere('id', $current->parent_id);
        }

        return array_values(
            AreaOptionResource::collection($parents->ordered()->get())->resolve()
        );
    }
}
