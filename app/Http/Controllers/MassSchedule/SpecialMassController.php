<?php

namespace App\Http\Controllers\MassSchedule;

use App\Concerns\MassScheduleFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassSchedule\StoreSpecialMassRequest;
use App\Http\Requests\MassSchedule\UpdateSpecialMassRequest;
use App\Http\Resources\MassSchedule\LocationOptionResource;
use App\Http\Resources\MassSchedule\SpecialMassResource;
use App\Models\Location;
use App\Models\SpecialMass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin CRUD for one-off masses, at /admin/misa-khusus.
 *
 * A mass that has already happened is kept, not removed (MASS-02). The list
 * shows them with an "inactive" marker rather than hiding them, because the
 * parish's own record of what happened in December is worth keeping — only the
 * public page stops showing them, via SpecialMass::scopeUpcoming.
 *
 * There is no destroy, for the same reason as everywhere else in this module.
 * See D-34.
 */
class SpecialMassController extends Controller
{
    use MassScheduleFormMeta;
    use SortsAdminList;

    /**
     * The special-mass list.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', SpecialMass::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $specialMasses = SpecialMass::query()
            ->with('location')
            // Joined so "Lokasi" can be a sortable column; see the note in
            // MassScheduleController::misaRutin(). Many-to-one, no duplication.
            ->leftJoin('locations', 'locations.id', '=', 'special_masses.location_id')
            ->select('special_masses.*')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('special_masses.title', 'like', "%{$search}%"))
            ->when(in_array($status, ['upcoming', 'past', 'inactive'], true), function (Builder $query) use ($status): void {
                match ($status) {
                    'inactive' => $query->where('special_masses.is_active', false),
                    // 'past' is a filter over what exists, not a separate
                    // record set: the rows are all still there.
                    'past' => $query->where('special_masses.start_at', '<', now()),
                    default => $query->where('special_masses.start_at', '>=', now())
                        ->where('special_masses.is_active', true),
                };
            })
            // How many notices point here, so the delete dialog can say what
            // stops being published. See D-40.
            ->withCount('scheduleNotices');

        $specialMasses = $this->applyAdminSort(
            $specialMasses,
            $request,
            [
                'title' => 'special_masses.title',
                'start_at' => 'special_masses.start_at',
                'end_at' => 'special_masses.end_at',
                'location' => 'locations.name',
                'is_active' => 'special_masses.is_active',
            ],
            [['special_masses.start_at', 'asc']],
        )->paginate($this->perPage($request))
            ->withQueryString();

        return Inertia::render('admin/jadwal-misa/misa-khusus/index', [
            'specialMasses' => SpecialMassResource::collection($specialMasses),
            'locations' => array_values(
                LocationOptionResource::collection($this->activeLocations())->resolve()
            ),
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
        Gate::authorize('create', SpecialMass::class);

        return Inertia::render('admin/jadwal-misa/misa-khusus/tambah', [
            'locations' => array_values(
                LocationOptionResource::collection($this->activeLocations())->resolve()
            ),
            ...$this->formMeta(),
        ]);
    }

    public function store(StoreSpecialMassRequest $request): RedirectResponse
    {
        SpecialMass::create($request->specialMassPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Misa khusus berhasil ditambahkan.')]);

        return to_route('special-masses.index');
    }

    public function edit(SpecialMass $specialMass): Response
    {
        Gate::authorize('update', $specialMass);

        return Inertia::render('admin/jadwal-misa/misa-khusus/ubah', [
            'specialMass' => SpecialMassResource::make($specialMass->load('location'))->resolve(),
            'locations' => array_values(
                LocationOptionResource::collection($this->activeLocations())->resolve()
            ),
            ...$this->formMeta(),
        ]);
    }

    public function update(UpdateSpecialMassRequest $request, SpecialMass $specialMass): RedirectResponse
    {
        $specialMass->update($request->specialMassPayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Misa khusus berhasil diperbarui.')]);

        return to_route('special-masses.index');
    }

    /**
     * Delete a one-off mass, softly.
     *
     * Past masses are not protected: MASS-02 says a passed mass stops being
     * listed, not that it is untouchable, and a super admin clearing out last
     * year's entries is a real job. Deactivate first when the intent is to keep
     * it off the site rather than remove it - that distinction is the whole
     * reason both verbs exist.
     */
    public function destroy(SpecialMass $specialMass): RedirectResponse
    {
        Gate::authorize('delete', $specialMass);

        $specialMass->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Misa khusus berhasil dihapus.')]);

        return to_route('special-masses.index');
    }

    /**
     * @return Collection<int, Location>
     */
    private function activeLocations(): Collection
    {
        return Location::query()->active()->orderBy('name')->get();
    }
}
