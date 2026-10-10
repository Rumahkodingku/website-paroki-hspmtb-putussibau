<?php

namespace App\Http\Controllers\MassSchedule;

use App\Concerns\MassScheduleFormMeta;
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
            ->when($search !== '', fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%"))
            ->when(in_array($status, ['upcoming', 'past', 'inactive'], true), function (Builder $query) use ($status): void {
                match ($status) {
                    'inactive' => $query->where('is_active', false),
                    // 'past' is a filter over what exists, not a separate
                    // record set: the rows are all still there.
                    'past' => $query->where('start_at', '<', now()),
                    default => $query->where('start_at', '>=', now())->where('is_active', true),
                };
            })
            ->orderBy('start_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/jadwal-misa/misa-khusus', [
            'specialMasses' => SpecialMassResource::collection($specialMasses),
            'locations' => array_values(
                LocationOptionResource::collection($this->activeLocations())->resolve()
            ),
            ...$this->formMeta(),
            'filters' => [
                'search' => $search,
                'status' => $status,
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

        return to_route('special-masses.index')
            ->with('success', __('Misa khusus berhasil ditambahkan.'));
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

        return to_route('special-masses.index')
            ->with('success', __('Misa khusus berhasil diperbarui.'));
    }

    /**
     * @return Collection<int, Location>
     */
    private function activeLocations(): Collection
    {
        return Location::query()->active()->orderBy('name')->get();
    }
}
