<?php

namespace App\Http\Controllers\MassSchedule;

use App\Actions\MassSchedule\DuplicateMassScheduleAction;
use App\Concerns\MassScheduleFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassSchedule\DuplicateMassScheduleRequest;
use App\Http\Requests\MassSchedule\StoreMassScheduleRequest;
use App\Http\Requests\MassSchedule\UpdateMassScheduleRequest;
use App\Http\Resources\MassSchedule\LocationOptionResource;
use App\Http\Resources\MassSchedule\MassScheduleResource;
use App\Models\Location;
use App\Models\MassSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin list of recurring masses, at /admin/jadwal-misa.
 *
 * This page carries two jobs and the URL decides which is which. PRD 5.2 maps
 * /admin/jadwal-misa to the recurring schedules; roadmap 6.1 asks for a module
 * summary with four shortcuts. Putting the counts and the shortcuts above the
 * table satisfies both without a second URL or a redirect — the alternative D-4
 * rejected.
 *
 * The four counts are read from the database rather than assumed, and each uses
 * the same scope the public page uses. That is the point: a number saying "3
 * active locations" here cannot disagree with a public page showing four,
 * because both are asking MassSchedule::scopeActive the same question.
 *
 * @see docs/DECISIONS.md D-38
 */
class MassScheduleController extends Controller
{
    use MassScheduleFormMeta;
    use SortsAdminList;

    /**
     * The recurring-mass list.
     *
     * Filtering and sorting both live in the URL rather than in component
     * state, per ARCHITECTURE.md Part B section 6: a shareable admin URL
     * survives a reload for free, and the back button steps through changes the
     * way an admin expects.
     */
    public function misaRutin(Request $request): Response
    {
        Gate::authorize('viewAny', MassSchedule::class);

        $search = $request->string('search')->toString();
        $locationId = $request->string('location_id')->toString();
        $status = $request->string('status')->toString();

        $massSchedules = MassSchedule::query()
            ->with('location')
            // Joined rather than filtered through whereHas because "Lokasi" is a
            // sortable column and ORDER BY a joined column is not something
            // whereHas can express. Many-to-one, so no row is duplicated.
            ->leftJoin('locations', 'locations.id', '=', 'mass_schedules.location_id')
            // Before withCount(), not after. withCount() adds its count as an
            // extra select expression, and a later select() replaces the whole
            // list — so putting this below the counts drops them silently and
            // the delete dialog reads an absent field as "nothing points here".
            // Nothing fails; the number is simply always zero.

            // Grouped by day with Sunday first, because the public page groups
            // them that way and an admin comparing the two should not have to
            // remember which order this one used.
            ->when($search !== '', fn (Builder $query) => $query
                ->where(function (Builder $inner) use ($search): void {
                    $inner->where('mass_schedules.name', 'like', "%{$search}%")
                        ->orWhere('locations.name', 'like', "%{$search}%");
                }))
            ->select('mass_schedules.*')
            ->when($locationId !== '', fn (Builder $query) => $query->where('mass_schedules.location_id', $locationId))
            // Anything other than 'active' or 'inactive' is left alone rather
            // than defaulted, so a stray ?status= is not silently a filter.
            ->when(in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('mass_schedules.is_active', $status === 'active'))
            // How many notices point here, so the delete dialog can say what
            // stops being published. See D-40.
            ->withCount('scheduleNotices');

        $massSchedules = $this->applyAdminSort(
            $massSchedules,
            $request,
            [
                'name' => 'mass_schedules.name',
                'day_of_week' => 'mass_schedules.day_of_week',
                'start_time' => 'mass_schedules.start_time',
                'location' => 'locations.name',
                'is_active' => 'mass_schedules.is_active',
            ],
            // Grouped by day with Sunday first, because the public page groups
            // them that way and an admin comparing the two should not have to
            // remember which order this one used.
            [
                ['mass_schedules.day_of_week', 'asc'],
                ['mass_schedules.start_time', 'asc'],
            ],
        )->paginate($this->perPage($request))
            ->withQueryString();

        return Inertia::render('admin/jadwal-misa/misa-rutin/index', [
            'massSchedules' => MassScheduleResource::collection($massSchedules),
            'locations' => array_values(
                LocationOptionResource::collection($this->activeLocations())->resolve()
            ),
            ...$this->formMeta(),
            'filters' => [
                'search' => $search,
                'location_id' => $locationId,
                'status' => $status,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
        ]);
    }

    /**
     * The create form.
     */
    public function create(): Response
    {
        Gate::authorize('create', MassSchedule::class);

        return Inertia::render('admin/jadwal-misa/misa-rutin/tambah', [
            'locations' => array_values(
                LocationOptionResource::collection($this->activeLocations())->resolve()
            ),
            ...$this->formMeta(),
        ]);
    }

    /**
     * Store a recurring mass.
     */
    public function store(StoreMassScheduleRequest $request): RedirectResponse
    {
        MassSchedule::create($request->massSchedulePayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Jadwal misa rutin berhasil ditambahkan.')]);

        return to_route('mass-schedules.list');
    }

    /**
     * The edit form.
     */
    public function edit(MassSchedule $massSchedule): Response
    {
        Gate::authorize('update', $massSchedule);

        return Inertia::render('admin/jadwal-misa/misa-rutin/ubah', [
            'massSchedule' => MassScheduleResource::make($massSchedule->load('location'))->resolve(),
            'locations' => array_values(
                LocationOptionResource::collection($this->activeLocations())->resolve()
            ),
            ...$this->formMeta(),
        ]);
    }

    /**
     * Save a recurring mass.
     *
     * is_active travels in the payload rather than having its own endpoint
     * (D-6). MASS-04 is still enforced: switching a schedule off hides it from
     * the public page and does not remove the row.
     */
    public function update(UpdateMassScheduleRequest $request, MassSchedule $massSchedule): RedirectResponse
    {
        $massSchedule->update($request->massSchedulePayload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Jadwal misa rutin berhasil diperbarui.')]);

        return to_route('mass-schedules.list');
    }

    /**
     * Copy a recurring mass to another location.
     *
     * The request already checked that the destination is active and free of
     * this day and time. The unique index check is repeated here anyway, because
     * the two requests can both pass validation and only one insert can win —
     * that race is the whole reason roadmap 5.2 asks for two layers, and
     * catching QueryException is what turns it into a message instead of a 500.
     */
    public function duplicate(
        DuplicateMassScheduleRequest $request,
        MassSchedule $massSchedule,
        DuplicateMassScheduleAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $massSchedule);

        $destination = Location::query()->findOrFail($request->destinationPayload()['location_id']);

        try {
            $action->handle($massSchedule, $destination);
        } catch (QueryException $exception) {
            // 23000 is MySQL's integrity-constraint violation, which includes
            // the duplicate-key case. Anything else is a real fault and is left
            // to the handler rather than dressed up as a duplicate.
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            return back()->withErrors(['error' => __('Sudah ada jadwal pada lokasi, hari, dan jam tersebut.')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Jadwal berhasil diduplikasi ke lokasi tujuan.')]);

        return to_route('mass-schedules.list');
    }

    /**
     * Delete a recurring mass, softly.
     *
     * Nothing blocks this one: a mass schedule has no children of its own. What
     * can be pointing at it is a notice, and that does not stop the delete -
     * the notice keeps its foreign key and simply stops appearing on the public
     * page, because its target is gone (D-40). The admin list shows a badge for
     * exactly that case so it is not a silent loss.
     */
    public function destroy(MassSchedule $massSchedule): RedirectResponse
    {
        Gate::authorize('delete', $massSchedule);

        $massSchedule->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Jadwal misa rutin berhasil dihapus.')]);

        return to_route('mass-schedules.list');
    }

    /**
     * Locations a schedule may point at.
     *
     * Only active ones. MASS-03 requires it, and offering a deactivated location
     * in the picker would let an admin fill in a form the server then refuses —
     * a worse experience than the option simply not being there.
     *
     * @return Collection<int, Location>
     */
    private function activeLocations(): Collection
    {
        return Location::query()->active()->orderBy('name')->get();
    }
}
