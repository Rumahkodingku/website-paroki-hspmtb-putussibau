<?php

namespace App\Http\Controllers\MassSchedule;

use App\Concerns\MassScheduleFormMeta;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
            // Newest window first: the notice an admin just wrote is the one
            // they are looking for.
            ->orderByDesc('starts_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/jadwal-misa/pemberitahuan', [
            'notices' => ScheduleNoticeResource::collection($notices),
            'targets' => $this->targets(),
            ...$this->formMeta(),
            'filters' => [
                'search' => $search,
                'status' => $status,
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
        ScheduleNotice::create($request->scheduleNoticePayload());

        return to_route('schedule-notices.index')
            ->with('success', __('Pemberitahuan berhasil ditambahkan.'));
    }

    public function edit(ScheduleNotice $scheduleNotice): Response
    {
        Gate::authorize('update', $scheduleNotice);

        return Inertia::render('admin/jadwal-misa/pemberitahuan/ubah', [
            'notice' => ScheduleNoticeResource::make(
                $scheduleNotice->load(['location', 'massSchedule', 'specialMass'])
            ),
            'targets' => $this->targets(),
            ...$this->formMeta(),
        ]);
    }

    public function update(UpdateScheduleNoticeRequest $request, ScheduleNotice $scheduleNotice): RedirectResponse
    {
        $scheduleNotice->update($request->scheduleNoticePayload());

        return to_route('schedule-notices.index')
            ->with('success', __('Pemberitahuan berhasil diperbarui.'));
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
     * The choices a notice's target picker offers.
     *
     * Past special masses are excluded here: a notice about a mass that already
     * happened has nothing to announce. Inactive schedules stay, because "the
     * Sunday mass at Stasi is suspended" is exactly what a notice is for.
     *
     * @return array<string, AnonymousResourceCollection>
     */
    private function targets(): array
    {
        return [
            'locations' => LocationOptionResource::collection(
                Location::query()->active()->orderBy('name')->get()
            ),
            'mass_schedules' => MassScheduleOptionResource::collection(
                MassSchedule::query()->with('location')->orderBy('day_of_week')->orderBy('start_time')->get()
            ),
            'special_masses' => SpecialMassOptionResource::collection(
                SpecialMass::query()->where('start_at', '>=', now())->orderBy('start_at')->get()
            ),
        ];
    }
}
