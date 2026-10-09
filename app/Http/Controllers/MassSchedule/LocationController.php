<?php

namespace App\Http\Controllers\MassSchedule;

use App\Concerns\MassScheduleFormMeta;
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

/**
 * Admin CRUD for locations, at /admin/lokasi.
 *
 * There is no destroy method and no delete route. PRD 7.3 says the admin
 * "nonaktifkan, tidak hapus", roadmap 5.6 forbids a permanent-delete endpoint
 * while deactivation covers the need, and roadmap 4.5 forbids removing a
 * location that still has schedules. is_active is the whole lifecycle. See
 * D-34.
 */
class LocationController extends Controller
{
    use MassScheduleFormMeta;

    /**
     * The location list.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Location::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $locations = Location::query()
            // active_schedules_count is what MASS-07's confirmation dialog reads.
            // Counting it here rather than in the resource keeps the query in one
            // place and the payload in the other.
            ->withCount(['massSchedules as active_schedules_count' => fn (Builder $query) => $query
                ->where('is_active', true)])
            ->when($search !== '', fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%"))
            ->when(in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/jadwal-misa/lokasi', [
            'locations' => LocationResource::collection($locations),
            ...$this->formMeta(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * The create form.
     */
    public function create(): Response
    {
        Gate::authorize('create', Location::class);

        return Inertia::render('admin/jadwal-misa/lokasi/tambah', $this->formMeta());
    }

    /**
     * Store a new location.
     */
    public function store(StoreLocationRequest $request): RedirectResponse
    {
        Location::create($request->locationPayload());

        return to_route('locations.index')
            ->with('success', __('Lokasi berhasil ditambahkan.'));
    }

    /**
     * The edit form.
     */
    public function edit(Location $location): Response
    {
        Gate::authorize('update', $location);

        return Inertia::render('admin/jadwal-misa/lokasi/ubah', [
            'location' => LocationResource::make($location->load('massSchedules'))->resolve(),
            ...$this->formMeta(),
        ]);
    }

    /**
     * Save a location.
     *
     * is_active travels in the payload rather than having its own endpoint
     * (D-6). The consequence worth knowing: turning a location off is an ordinary
     * update, so the confirmation MASS-07 asks for is a frontend concern. The
     * backend guarantee is not the dialog — it is that MassSchedule::scopeActive
     * and Location::scopeActive hide the location and its schedules from the
     * public page whatever the client does.
     */
    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->locationPayload());

        return to_route('locations.index')
            ->with('success', __('Lokasi berhasil diperbarui.'));
    }
}
