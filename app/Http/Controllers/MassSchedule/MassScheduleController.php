<?php

namespace App\Http\Controllers\MassSchedule;

use App\Actions\MassSchedule\DuplicateMassScheduleAction;
use App\Concerns\MassScheduleFormMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassSchedule\DuplicateMassScheduleRequest;
use App\Http\Requests\MassSchedule\StoreMassScheduleRequest;
use App\Http\Requests\MassSchedule\UpdateMassScheduleRequest;
use App\Http\Resources\MassSchedule\LocationOptionResource;
use App\Http\Resources\MassSchedule\MassScheduleResource;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\ScheduleNotice;
use App\Models\SpecialMass;
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

    /**
     * The module summary and the recurring-mass list.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', MassSchedule::class);

        $search = $request->string('search')->toString();
        $locationId = $request->string('location_id')->toString();
        $status = $request->string('status')->toString();

        $massSchedules = MassSchedule::query()
            ->with('location')
            // Grouped by day with Sunday first, because the public page groups
            // them that way and an admin comparing the two should not have to
            // remember which order this one used.
            ->when($search !== '', fn (Builder $query) => $query
                ->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhereHas('location', fn (Builder $locations) => $locations
                            ->where('name', 'like', "%{$search}%"));
                }))
            ->when($locationId !== '', fn (Builder $query) => $query->where('location_id', $locationId))
            // Anything other than 'active' or 'inactive' is left alone rather
            // than defaulted, so a stray ?status= is not silently a filter.
            ->when(in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/jadwal-misa', [
            'massSchedules' => MassScheduleResource::collection($massSchedules),
            'locations' => LocationOptionResource::collection($this->activeLocations()),
            'stats' => self::stats(),
            ...$this->formMeta(),
            'filters' => [
                'search' => $search,
                'location_id' => $locationId,
                'status' => $status,
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
            'locations' => LocationOptionResource::collection($this->activeLocations()),
            ...$this->formMeta(),
        ]);
    }

    /**
     * Store a recurring mass.
     */
    public function store(StoreMassScheduleRequest $request): RedirectResponse
    {
        MassSchedule::create($request->massSchedulePayload());

        return to_route('mass-schedules.index')
            ->with('success', __('Jadwal misa rutin berhasil ditambahkan.'));
    }

    /**
     * The edit form.
     */
    public function edit(MassSchedule $massSchedule): Response
    {
        Gate::authorize('update', $massSchedule);

        return Inertia::render('admin/jadwal-misa/misa-rutin/ubah', [
            'massSchedule' => MassScheduleResource::make($massSchedule->load('location'))->resolve(),
            'locations' => LocationOptionResource::collection($this->activeLocations()),
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

        return to_route('mass-schedules.index')
            ->with('success', __('Jadwal misa rutin berhasil diperbarui.'));
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

            return back()->with('error', __('Sudah ada jadwal pada lokasi, hari, dan jam tersebut.'));
        }

        return to_route('mass-schedules.index')
            ->with('success', __('Jadwal berhasil diduplikasi ke lokasi tujuan.'));
    }

    /**
     * The four numbers the summary shows.
     *
     * @return array{active_mass_schedules: int, active_locations: int, upcoming_special_masses: int, visible_notices: int}
     */
    public static function stats(): array
    {
        return [
            'active_mass_schedules' => MassSchedule::query()->active()->count(),
            'active_locations' => Location::query()->active()->count(),
            'upcoming_special_masses' => SpecialMass::query()->upcoming()->count(),
            'visible_notices' => ScheduleNotice::query()->currentlyVisible()->count(),
        ];
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
