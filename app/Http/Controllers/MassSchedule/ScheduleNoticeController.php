<?php

namespace App\Http\Controllers\MassSchedule;

use App\Concerns\MassScheduleFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassSchedule\StoreScheduleNoticeRequest;
use App\Http\Requests\MassSchedule\UpdateScheduleNoticeRequest;
use App\Http\Resources\MassSchedule\LocationOptionResource;
use App\Http\Resources\MassSchedule\MassScheduleOptionResource;
use App\Http\Resources\MassSchedule\ScheduleNoticeResource;
use App\Http\Resources\MassSchedule\SpecialMassOptionResource;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\ScheduleNotice;
use App\Models\SpecialMass;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin CRUD for schedule-change notices, at /admin/pemberitahuan-jadwal.
 *
 * The list shows every notice including the ones no longer visible, because an
 * admin asking "did that January notice ever go up?" needs an answer. Whether a
 * notice is currently shown is a derived status on the row, not a filter applied
 * to the query — ScheduleNoticeResource computes it from is_active and the
 * display window, which is MASS-05's three conditions in one place.
 *
 * @see docs/DECISIONS.md D-34
 */
class ScheduleNoticeController extends Controller
{
    use MassScheduleFormMeta;

    public function __construct(private readonly AuditLogger $audit) {}

    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ScheduleNotice::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $notices = ScheduleNotice::query()
            ->with(['location', 'massSchedule', 'specialMass'])
            ->when($search !== '', fn (Builder $query) => $query
                ->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")))
            ->when(in_array($status, ['draft', 'scheduled', 'visible', 'expired'], true),
                fn (Builder $query) => $this->applyStatus($query, $status))
            // Three correlated counts rather than eager loading the targets
            // withTrashed(). The relation would have to come back as null for a
            // deleted row, which is indistinguishable from a notice that never
            // had a target - and this list exists to tell those two apart
            // (D-40). Counting is also one index lookup per row instead of three
            // hydrated models.
            //
            // withoutGlobalScope() is the load-bearing part. withCount() builds
            // the subquery from the relation, and the relation carries
            // SoftDeletingScope - so deleted rows are already excluded by the
            // time whereNotNull('deleted_at') runs and every count came back
            // zero. Asking the scope to stand down first is what makes this a
            // question about deleted rows at all.
            ->withCount(['location as trashed_location_count' => fn (Builder $query) => $query
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNotNull('deleted_at')])
            ->withCount(['massSchedule as trashed_mass_schedule_count' => fn (Builder $query) => $query
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNotNull('deleted_at')])
            ->withCount(['specialMass as trashed_special_mass_count' => fn (Builder $query) => $query
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNotNull('deleted_at')]);
        // Newest window first: the notice an admin just wrote is the one
        // they are looking for.

        $notices = $this->applyAdminSort(
            $notices,
            $request,
            [
                'title' => 'schedule_notices.title',
                'starts_at' => 'schedule_notices.starts_at',
                'ends_at' => 'schedule_notices.ends_at',
                'is_active' => 'schedule_notices.is_active',
            ],
            // Newest window first: the notice an admin just wrote is the one
            // they are looking for. 'status' is a derived column (MASS-05 puts
            // a notice in one of four states from three booleans), so there is
            // nothing to sort it by and it is absent from the whitelist too.
            [['schedule_notices.starts_at', 'desc']],
        )->paginate($this->perPage($request))
            ->withQueryString();

        return Inertia::render('admin/jadwal-misa/pemberitahuan/index', [
            'notices' => ScheduleNoticeResource::collection($notices),
            'targets' => $this->targets(),
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
        Gate::authorize('create', ScheduleNotice::class);

        return Inertia::render('admin/jadwal-misa/pemberitahuan/tambah', [
            'targets' => $this->targets(),
            ...$this->formMeta(),
        ]);
    }

    public function store(StoreScheduleNoticeRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $notice = ScheduleNotice::query()->create($request->scheduleNoticePayload());
            $this->audit->created('schedule_notices', $notice);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pemberitahuan berhasil ditambahkan.')]);

        return to_route('schedule-notices.index');
    }

    public function edit(ScheduleNotice $scheduleNotice): Response
    {
        Gate::authorize('update', $scheduleNotice);

        return Inertia::render('admin/jadwal-misa/pemberitahuan/ubah', [
            'notice' => ScheduleNoticeResource::make(
                $scheduleNotice->load(['location', 'massSchedule', 'specialMass'])
            )->resolve(),
            'targets' => $this->targets(),
            ...$this->formMeta(),
        ]);
    }

    public function update(UpdateScheduleNoticeRequest $request, ScheduleNotice $scheduleNotice): RedirectResponse
    {
        DB::transaction(function () use ($request, $scheduleNotice): void {
            $before = $this->audit->snapshot($scheduleNotice, 'schedule_notices');
            $wasActive = $scheduleNotice->is_active;

            $scheduleNotice->update($request->scheduleNoticePayload());

            $action = $wasActive === $scheduleNotice->is_active
                ? 'updated'
                : ($scheduleNotice->is_active ? 'activated' : 'deactivated');

            $this->audit->updated('schedule_notices', $scheduleNotice, $before, $action);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pemberitahuan berhasil diperbarui.')]);

        return to_route('schedule-notices.index');
    }

    /**
     * Restrict the list to one of the four display states.
     *
     * @param  Builder<ScheduleNotice>  $query
     */
    private function applyStatus(Builder $query, string $status): void
    {
        $now = now();

        match ($status) {
            'draft' => $query->where('is_active', false),
            'scheduled' => $query->where('is_active', true)->where('starts_at', '>', $now),
            'visible' => $query->where('is_active', true)
                ->where('starts_at', '<=', $now)
                ->where(fn (Builder $window) => $window->whereNull('ends_at')->orWhere('ends_at', '>=', $now)),
            default => $query->where('is_active', true)
                ->where('starts_at', '<=', $now)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', $now),
        };
    }

    /**
     * Delete a notice.
     *
     * The only one of the four with no dependents at all, which is why it is
     * also the only one that needs no warning about anything following it.
     */
    public function destroy(ScheduleNotice $scheduleNotice): RedirectResponse
    {
        Gate::authorize('delete', $scheduleNotice);

        DB::transaction(function () use ($scheduleNotice): void {
            $before = $this->audit->snapshot($scheduleNotice, 'schedule_notices');
            $scheduleNotice->delete();
            $this->audit->deleted('schedule_notices', $scheduleNotice, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pemberitahuan berhasil dihapus.')]);

        return to_route('schedule-notices.index');
    }

    /**
     * The choices a notice's target picker offers.
     *
     * Past special masses are excluded here: a notice about a mass that already
     * happened has nothing to announce. Inactive schedules stay, because "the
     * Sunday mass at Stasi is suspended" is exactly what a notice is for.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function targets(): array
    {
        return [
            'locations' => array_values(LocationOptionResource::collection(
                Location::query()->active()->orderBy('name')->get()
            )->resolve()),
            'mass_schedules' => array_values(MassScheduleOptionResource::collection(
                MassSchedule::query()->with('location')->orderBy('day_of_week')->orderBy('start_time')->get()
            )->resolve()),
            'special_masses' => array_values(SpecialMassOptionResource::collection(
                SpecialMass::query()->where('start_at', '>=', now())->orderBy('start_at')->get()
            )->resolve()),
        ];
    }
}
