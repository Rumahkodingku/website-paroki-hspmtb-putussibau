<?php

namespace App\Http\Controllers\Agenda;

use App\Actions\Agenda\DuplicateEventAction;
use App\Actions\Agenda\SaveEventAction;
use App\Concerns\EventFormMeta;
use App\Concerns\SortsAdminList;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\StoreEventRequest;
use App\Http\Requests\Agenda\UpdateEventRequest;
use App\Http\Resources\Agenda\EventCategoryOptionResource;
use App\Http\Resources\Agenda\EventResource;
use App\Http\Resources\MassSchedule\LocationOptionResource;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Activities — /admin/agenda
 *
 * A list, a form, a duplicate and a soft delete. The same shape as
 * PostController, and deliberately: the two tables describe the same kind of
 * thing to the same kind of user.
 *
 * The list offers two filters Jadwal Misa does not have, and both exist because
 * a parish asks different questions of the two. `bulan` is a period filter
 * because "what is on this month" is how the calendar is navigated, and
 * `is_published` because draft activities are the normal state of something
 * typed in last week for next month.
 *
 * @see docs/DECISIONS.md D-44
 */
class EventController extends Controller
{
    use EventFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Event::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $categoryId = $request->string('kategori')->toString();
        $month = $this->monthFilter($request->string('bulan')->toString());
        $published = $request->string('publikasi')->toString();

        $events = Event::query()
            ->with('category')
            ->with('location')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('events.title', 'like', "%{$search}%"))
            ->when(
                in_array($status, array_column(EventStatus::cases(), 'value'), true),
                fn (Builder $query) => $query->where('events.status', $status)
            )
            ->when($categoryId !== '', fn (Builder $query) => $query
                ->where('events.event_category_id', $categoryId))
            ->when($month !== null, fn (Builder $query) => $query
                // A month boundary in the display timezone, not in UTC: an
                // activity on the 1st at 00.30 WIB is still the 1st in the
                // parish's calendar and filtering by UTC would move it to the
                // previous month. See D-01.
                ->whereBetween('start_at', [
                    $month->copy()->startOfMonth()->utc(),
                    $month->copy()->endOfMonth()->utc(),
                ]))
            ->when(
                in_array($published, ['yes', 'no'], true),
                fn (Builder $query) => $query->where('events.is_published', $published === 'yes')
            );

        $events = $this->applyAdminSort(
            $events,
            $request,
            [
                'title' => 'events.title',
                'status' => 'events.status',
                'start_at' => 'events.start_at',
                'created_at' => 'events.created_at',
            ],
            [['events.start_at', 'desc']],
        )->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/agenda', [
            'events' => EventResource::collection($events),
            ...$this->eventFormMeta(),
            'eventCategories' => array_values(
                EventCategoryOptionResource::collection($this->categories())->resolve()
            ),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'kategori' => $categoryId,
                'bulan' => $month?->format('Y-m') ?? '',
                'publikasi' => $published,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Event::class);

        return Inertia::render('admin/agenda/tambah', [
            ...$this->eventFormMeta(),
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreEventRequest $request, SaveEventAction $action): RedirectResponse
    {
        $event = $action->handle(new Event, $request->eventPayload());

        return to_route('events.edit', $event)
            ->with('success', __('Agenda berhasil disimpan.'));
    }

    public function edit(Event $event): Response
    {
        Gate::authorize('update', $event);

        return Inertia::render('admin/agenda/ubah', [
            'event' => EventResource::make($event->load(['category', 'location']))->resolve(),
            ...$this->eventFormMeta(),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event, SaveEventAction $action): RedirectResponse
    {
        $action->handle($event, $request->eventPayload());

        return to_route('events.edit', $event)
            ->with('success', __('Agenda berhasil diperbarui.'));
    }

    /**
     * EVT-05 has no recurring activities, so duplication is how a monthly
     * meeting is entered. The copy is a draft and gets its own slug; see
     * DuplicateEventAction for why both.
     *
     * A POST, not a GET: roadmap 9 says avoid mutating endpoints on GET, and
     * this one writes a row.
     */
    public function duplicate(Event $event, DuplicateEventAction $action): RedirectResponse
    {
        Gate::authorize('update', $event);

        $copy = $action->handle($event, $action->defaultOverrides($event));

        // Straight to the edit form rather than to the list: a duplicate opens
        // with a start date a month out that nobody has confirmed, and the form
        // is where that gets confirmed.
        return to_route('events.edit', $copy)
            ->with('success', __('Agenda berhasil digandakan. Periksa tanggal dan lokasi sebelum diterbitkan.'));
    }

    /**
     * A soft delete (roadmap 8.6 has no permanent delete and PRD 9.7 marks
     * events (SD)).
     */
    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return to_route('events.list')
            ->with('success', __('Agenda berhasil dihapus.'));
    }

    /**
     * The dropdowns the form needs.
     *
     * Both resolve to plain arrays rather than resource collections handed over
     * unresolved — see D-39. A collection serialises as {"data": [...]}, React
     * then calls .map on an object, and the page renders blank while every test
     * is green.
     *
     * Every location, not only the active ones: an activity may point at a
     * location the parish has retired, and hiding it from the dropdown would
     * make editing that activity impossible. See EventValidationRules.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'eventCategories' => array_values(
                EventCategoryOptionResource::collection($this->categories())->resolve()
            ),
            'locations' => array_values(
                LocationOptionResource::collection($this->locations())->resolve()
            ),
        ];
    }

    /**
     * @return Collection<int, EventCategory>
     */
    private function categories(): Collection
    {
        return EventCategory::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Location>
     */
    private function locations(): Collection
    {
        return Location::query()->orderBy('name')->get();
    }

    /**
     * A `YYYY-MM` filter, or no filter when the value is not one.
     *
     * Silently ignored rather than a 422, for the reason
     * PublicMassScheduleController::filteredDay() gives: a mangled link should
     * show a list, not an error.
     */
    private function monthFilter(string $value): ?Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return Carbon::createFromFormat(
            'Y-m-d',
            $value.'-01',
            config('app.display_timezone'),
        )->startOfMonth();
    }
}
