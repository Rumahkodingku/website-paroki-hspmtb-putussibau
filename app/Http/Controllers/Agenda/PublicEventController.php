<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Resources\MassSchedule\ResolvesNestedResources;
use App\Http\Resources\Public\Agenda\PublicEventCategoryResource;
use App\Http\Resources\Public\Agenda\PublicEventDetailResource;
use App\Http\Resources\Public\Agenda\PublicEventResource;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public agenda pages — /agenda and /agenda/{slug}
 *
 * Two modes over one set of published activities. The default is the list, on
 * every viewport: AC-EVT-2 says the list is what a phone shows, and the
 * calendar is something a visitor asks for. Making the mode a URL parameter
 * rather than a CSS decision matters for the same reason — the two modes show
 * different activities, and a stylesheet cannot do that.
 *
 * Every query starts from Event::published(). Soft-deleted rows are excluded by
 * the trait, and an unpublished one by the scope, which is what makes roadmap
 * 8.2's rule hold for the calendar file as well as for the page — the .ics
 * route binds through the same scope.
 *
 * @see docs/DECISIONS.md D-39, D-44
 */
class PublicEventController extends Controller
{
    use ResolvesNestedResources;

    /**
     * How many activities the upcoming list asks for.
     */
    private const UPCOMING_LIMIT = 12;

    public function index(Request $request): Response
    {
        $category = $this->filteredCategory($request->string('kategori')->toString());
        $month = $this->filteredMonth($request->string('bulan')->toString());
        $mode = $request->string('mode')->toString() === 'kalender' ? 'kalender' : 'daftar';

        return Inertia::render('public/agenda', [
            'mode' => $mode,
            // Both lists are sent. A visitor switching to the calendar should not
            // wait for a round trip to discover the month has activities, and
            // the default month is the one they are already looking at.
            'upcoming' => $this->upcoming($category),
            'monthly' => $this->monthly($month, $category),
            'categories' => array_values(
                PublicEventCategoryResource::collection($this->categories())->resolve()
            ),
            'today' => $this->todayKey(),
            'filters' => [
                'mode' => $mode,
                'kategori' => $category instanceof EventCategory ? $category->slug : '',
                'bulan' => $month->format('Y-m'),
            ],
        ]);
    }

    public function show(Event $event): Response
    {
        return Inertia::render('public/agenda/show', [
            'event' => PublicEventDetailResource::make($event->load('category', 'location'))->resolve(),
            'related' => $this->related($event),
        ]);
    }

    /**
     * What is ahead, nearest first (EVT-02).
     *
     * "Ahead" means from the start of today in the parish's timezone, not from
     * `now()`. An activity that began an hour ago and has not finished is
     * still something somebody might walk into, and one that began this morning
     * belongs on today's list rather than yesterday's.
     *
     * @return list<array<string, mixed>>
     */
    private function upcoming(?EventCategory $category): array
    {
        $startOfToday = Carbon::now()
            ->setTimezone((string) config('app.display_timezone'))
            ->startOfDay()
            ->utc();

        return array_values(PublicEventResource::collection(
            Event::query()
                ->published()
                // location as well as category: displayLocationName() reads the
                // master location, and shouldBeStrict() turns the lazy load into
                // a 500 rather than an N+1.
                ->with(['category', 'location'])
                ->when($category !== null, fn (Builder $query) => $query
                    ->where('event_category_id', $category->getKey()))
                ->where('start_at', '>=', $startOfToday)
                ->orderBy('start_at')
                ->orderBy('id')
                ->limit(self::UPCOMING_LIMIT)
                ->get()
        )->resolve());
    }

    /**
     * One month of activities, as a flat list.
     *
     * Flat, not grouped by day. It used to return { '2026-11-14': [...] }, which
     * meant the same question — which day in Pontianak does this instant fall
     * on — had two answers in the codebase: dayKey() here and wibDateKey() in
     * resources/js/lib/datetime.ts. Two answers to a timezone question is one
     * too many, and the two disagreed about nothing visible only because they
     * happened to agree today.
     *
     * So this sends the list and the calendar grid groups it with wibDateKey,
     * which is the one that has its own tests. The grid is the only consumer
     * that needed the grouping anyway.
     *
     * @return list<array<string, mixed>>
     */
    private function monthly(Carbon $month, ?EventCategory $category): array
    {
        return array_values(PublicEventResource::collection(
            Event::query()
                ->published()
                // location as well as category: displayLocationName() reads the
                // master location, and shouldBeStrict() turns the lazy load into
                // a 500 rather than an N+1.
                ->with(['category', 'location'])
                ->when($category !== null, fn (Builder $query) => $query
                    ->where('event_category_id', $category->getKey()))
                ->whereBetween('start_at', [
                    $month->copy()->startOfMonth()->utc(),
                    $month->copy()->endOfMonth()->utc(),
                ])
                ->orderBy('start_at')
                ->orderBy('id')
                ->get()
        )->resolve());
    }

    /**
     * Other activities in the same category.
     *
     * Published-scoped for the same reason the news related posts are: a draft
     * must not surface here.
     *
     * @return list<array<string, mixed>>
     */
    private function related(Event $event): array
    {
        return array_values(PublicEventResource::collection(
            Event::query()
                ->published()
                ->where('event_category_id', $event->event_category_id)
                ->whereKeyNot($event->getKey())
                ->with('category')
                ->where('start_at', '>=', Carbon::now()->utc())
                ->orderBy('start_at')
                ->limit(3)
                ->get()
        )->resolve());
    }

    /**
     * @return Collection<int, EventCategory>
     */
    private function categories(): Collection
    {
        return EventCategory::query()
            ->whereHas('events', fn (Builder $query) => $query->published())
            ->orderBy('name')
            ->get();
    }

    private function todayKey(): string
    {
        return Carbon::now()
            ->setTimezone((string) config('app.display_timezone'))
            ->format('Y-m-d');
    }

    private function filteredCategory(string $slug): ?EventCategory
    {
        if ($slug === '') {
            return null;
        }

        return EventCategory::query()->where('slug', $slug)->first();
    }

    /**
     * The month being looked at, or this one.
     *
     * A malformed value falls back to the current month rather than erroring —
     * same reasoning as the public schedule's day filter.
     */
    private function filteredMonth(string $value): Carbon
    {
        $timezone = (string) config('app.display_timezone');

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            // The regex has already reduced this to `YYYY-MM-01`, so parse
            // cannot fail and createFromFormat's false branch would be dead
            // code that still has to be handled.
            return Carbon::parse($value.'-01', $timezone)->startOfMonth();
        }

        return Carbon::now()->setTimezone($timezone)->startOfMonth();
    }
}
