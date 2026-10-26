<?php

namespace App\Http\Controllers\MasterData;

use App\Concerns\MassScheduleFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassSchedule\StoreLocationRequest;
use App\Http\Requests\MassSchedule\UpdateLocationRequest;
use App\Http\Resources\MassSchedule\LocationResource;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    use MassScheduleFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Location::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $locations = Location::query()
            ->withCount(['massSchedules as active_schedules_count' => fn (Builder $query) => $query
                ->where('is_active', true)])
            ->withCount('massSchedules')
            ->withCount('specialMasses')
            ->withCount('scheduleNotices')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('locations.name', 'like', "%{$search}%"))
            ->when(
                in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('locations.is_active', $status === 'active')
            );

        $locations = $this->applyAdminSort(
            $locations,
            $request,
            [
                'name' => 'locations.name',
                'slug' => 'locations.slug',
                'type' => 'locations.type',
                'is_active' => 'locations.is_active',
            ],
            [['locations.name', 'asc']],
        )->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/master-data/lokasi/index', [
            'locations' => LocationResource::collection($locations),
            ...$this->formMeta(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Location::class);

        return Inertia::render('admin/master-data/lokasi/tambah', $this->formMeta());
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        Location::create($request->locationPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lokasi berhasil ditambahkan.')]);

        return to_route('locations.index');
    }

    public function edit(Location $location): Response
    {
        Gate::authorize('update', $location);

        return Inertia::render('admin/master-data/lokasi/ubah', [
            'location' => LocationResource::make($location->load('massSchedules'))->resolve(),
            ...$this->formMeta(),
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->locationPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lokasi berhasil diperbarui.')]);

        return to_route('locations.index');
    }

    public function destroy(Location $location): RedirectResponse
    {
        Gate::authorize('delete', $location);

        $schedules = $location->massSchedules()->count();
        $specialMasses = $location->specialMasses()->count();

        if ($schedules > 0 || $specialMasses > 0) {
            return to_route('locations.index')->withErrors([
                'location' => $this->occupiedMessage($schedules, $specialMasses),
            ]);
        }

        $location->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lokasi berhasil dihapus.')]);

        return to_route('locations.index');
    }

    /**
     * The refusal, naming what is in the way.
     *
     * Two separate counts because an admin who is told "this location has
     * schedules" and then finds a special mass as well has learned nothing they
     * can act on.
     */
    private function occupiedMessage(int $schedules, int $specialMasses): string
    {
        $parts = [];

        if ($schedules > 0) {
            $parts[] = trans_choice(
                '{1} :count jadwal misa rutin|[2,*] :count jadwal misa rutin',
                $schedules,
                ['count' => $schedules]
            );
        }

        if ($specialMasses > 0) {
            $parts[] = trans_choice(
                '{1} :count misa khusus|[2,*] :count misa khusus',
                $specialMasses,
                ['count' => $specialMasses]
            );
        }

        return __('Lokasi tidak dapat dihapus karena masih memiliki :items. Hapus atau nonaktifkan isinya terlebih dahulu.', [
            'items' => implode(' dan ', $parts),
        ]);
    }
}
