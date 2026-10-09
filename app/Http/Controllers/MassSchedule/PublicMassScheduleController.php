<?php

namespace App\Http\Controllers\MassSchedule;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Http\Resources\Public\MassSchedule\NextMassResource;
use App\Http\Resources\Public\MassSchedule\PublicMassScheduleResource;
use App\Http\Resources\Public\MassSchedule\PublicScheduleNoticeResource;
use App\Http\Resources\Public\MassSchedule\PublicSpecialMassResource;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\ScheduleNotice;
use App\Models\SpecialMass;
use App\Services\NextMassResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public schedule page, at /jadwal-misa.
 *
 * PRD 7.3 calls this "the page visitors look for most". Everything on it is
 * derived here rather than in React, for three reasons that all come back to
 * the same place: the next mass is a calculation (roadmap 8), the filters are a
 * query rather than a client-side slice, and a schedule at a deactivated location
 * must not be sent to the browser at all (MASS-07) — hiding it in React would
 * still put it in the page source.
 *
 * The filters live in the query string so a filtered schedule can be shared,
 * which roadmap 7.2 asks for. A filter that arrives invalid is ignored rather
 * than rejected: /jadwal-misa?hari=99 should show the full schedule, not a 422 to
 * somebody who followed a mangled link.
 *
 * The three lists resolve to plain arrays rather than being handed over as
 * resource collections. A collection serialises as {"data": [...]}, which is
 * right for a paginated admin list — it is where links and meta live — and wrong
 * here: the public page would then carry a data envelope around a list that is
 * never paginated, and the TypeScript type would have to unwrap it. Paginated<T>
 * keeps the wrapper; a plain list does not.
 */
class PublicMassScheduleController extends Controller
{
    public function __construct(private readonly NextMassResolver $resolver) {}

    public function index(Request $request): Response
    {
        $location = $this->filteredLocation($request);
        $day = $this->filteredDay($request);

        return Inertia::render('public/jadwal-misa', [
            'nextMass' => $this->nextMass($location),
            'regularMasses' => $this->regularMasses($location, $day),
            'specialMasses' => $this->specialMasses($location),
            'notices' => $this->notices($location),
            'locations' => Location::query()->active()->orderBy('name')->get(),
            'filters' => [
                'location_id' => $location?->getKey(),
                'day' => $day?->value,
            ],
        ]);
    }

    /**
     * Recurring masses.
     *
     * Ordered by day then time and grouped on the React side, because the order
     * is already the grouping — regrouping here would mean shipping the same
     * rows inside a second nested object for no reader.
     *
     * The day filter is applied in SQL rather than in the browser, so a filtered
     * list really is the only list that left the server.
     *
     * @return list<array<string, mixed>>
     */
    public function regularMasses(?Location $location, ?DayOfWeek $day): array
    {
        return array_values(PublicMassScheduleResource::collection(
            MassSchedule::query()
                ->active()
                ->when($location, fn (Builder $query) => $query->where('location_id', $location->getKey()))
                ->when($day, fn (Builder $query) => $query->where('day_of_week', $day->value))
                ->with('location')
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
        )->resolve());
    }

    /**
     * Special masses still to come, soonest first (MASS-02).
     *
     * @return list<array<string, mixed>>
     */
    public function specialMasses(?Location $location): array
    {
        return array_values(PublicSpecialMassResource::collection(
            SpecialMass::query()
                ->upcoming()
                ->when($location, fn (Builder $query) => $query->where('location_id', $location->getKey()))
                ->with('location')
                ->orderBy('start_at')
                ->get()
        )->resolve());
    }

    /**
     * Notices to show as a banner.
     *
     * General notices always apply. A targeted notice applies only when the
     * visitor is looking at that target: a notice about one schedule should not
     * appear above an unfiltered list as though it concerned every mass there.
     * That is what roadmap 7.1 means by showing notices "sesuai targetnya".
     *
     * @return list<array<string, mixed>>
     */
    public function notices(?Location $location): array
    {
        return array_values(PublicScheduleNoticeResource::collection(
            ScheduleNotice::query()
                ->currentlyVisible()
                ->where(fn (Builder $query) => $query
                    // No target: applies to everyone.
                    ->whereNull('location_id')
                    ->whereNull('mass_schedule_id')
                    ->whereNull('special_mass_id')
                    // Or it matches the filter currently in effect.
                    ->when($location, fn (Builder $targeted) => $targeted
                        ->where('location_id', $location->getKey())))
                ->orderByDesc('starts_at')
                ->get()
        )->resolve());
    }

    /**
     * The location filter, or null when absent or unusable.
     *
     * The lookup goes through scopeActive rather than find(), so a link with a
     * deactivated location's id shows the whole schedule instead of an empty
     * page with no explanation.
     */
    private function filteredLocation(Request $request): ?Location
    {
        $id = $request->integer('lokasi');

        if ($id <= 0) {
            return null;
        }

        return Location::query()->active()->find($id);
    }

    /**
     * The day filter, or null when absent or out of range.
     */
    private function filteredDay(Request $request): ?DayOfWeek
    {
        if (! $request->has('hari')) {
            return null;
        }

        return DayOfWeek::tryFromInt($request->integer('hari'));
    }

    /**
     * The "Misa Berikutnya" card's data, or null for a polite empty state.
     *
     * Resolved rather than handed over as a resource, for the same reason the
     * three lists above are: a resource serialises as {"data": ...} and this
     * page's props are plain values.
     *
     * Scoped to the filtered location for the same reason the lists are: a
     * visitor who chose a stasi and sees the parish church's next mass has been
     * shown something that is not at the place they asked about.
     */
    /**
     * @return array<string, mixed>|null
     */
    private function nextMass(?Location $location): ?array
    {
        $next = $location === null
            ? $this->resolver->resolve()
            : $this->resolver->resolveFor($location);

        return $next === null ? null : NextMassResource::make($next)->resolve();
    }
}
