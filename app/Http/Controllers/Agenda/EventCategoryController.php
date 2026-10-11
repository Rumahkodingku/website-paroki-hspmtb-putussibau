<?php

namespace App\Http\Controllers\Agenda;

use App\Concerns\EventFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\StoreEventCategoryRequest;
use App\Http\Requests\Agenda\UpdateEventCategoryRequest;
use App\Http\Resources\Agenda\EventCategoryResource;
use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Activity categories — /admin/kategori-agenda
 *
 * The same shape as PostCategoryController, and deliberately so: the two tables
 * differ by one column and a different list order, and a second controller
 * would be a second set of decisions about the same question.
 *
 * No sort whitelist entry for `color`. It is stored, editable and rendered, but
 * ordering a list by hue is not a thing anybody needs, and D-42's lesson
 * applies in reverse here — an entry nobody clicks is an entry that has to be
 * removed when the column is.
 *
 * @see docs/DECISIONS.md D-44
 */
class EventCategoryController extends Controller
{
    use EventFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', EventCategory::class);

        $search = $request->string('search')->toString();

        $eventCategories = EventCategory::query()
            // Before a select() that might follow it. See D-41.
            ->withCount('events')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('event_categories.name', 'like', "%{$search}%"));

        $eventCategories = $this->applyAdminSort(
            $eventCategories,
            $request,
            [
                'name' => 'event_categories.name',
                'slug' => 'event_categories.slug',
            ],
            // No sort_order column: roadmap 5.3 does not ask for one, and the
            // agenda list is ordered by date. Name is the only stable order.
            [['event_categories.name', 'asc']],
        )->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/master-data/kategori-agenda/index', [
            'eventCategories' => EventCategoryResource::collection($eventCategories),
            ...$this->eventFormMeta(),
            'filters' => [
                'search' => $search,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', EventCategory::class);

        return Inertia::render('admin/master-data/kategori-agenda/tambah', $this->eventFormMeta());
    }

    public function store(StoreEventCategoryRequest $request): RedirectResponse
    {
        EventCategory::create($request->eventCategoryPayload());

        return to_route('event-categories.index')
            ->with('success', __('Kategori agenda berhasil ditambahkan.'));
    }

    public function edit(EventCategory $eventCategory): Response
    {
        Gate::authorize('update', $eventCategory);

        return Inertia::render('admin/master-data/kategori-agenda/ubah', [
            'eventCategory' => EventCategoryResource::make($eventCategory)->resolve(),
            ...$this->eventFormMeta(),
        ]);
    }

    public function update(UpdateEventCategoryRequest $request, EventCategory $eventCategory): RedirectResponse
    {
        $eventCategory->update($request->eventCategoryPayload());

        return to_route('event-categories.index')
            ->with('success', __('Kategori agenda berhasil diperbarui.'));
    }

    public function destroy(EventCategory $eventCategory): RedirectResponse
    {
        Gate::authorize('delete', $eventCategory);

        // withTrashed() because a soft-deleted activity is still a row holding
        // the foreign key, and the RESTRICT constraint would answer the delete
        // with a QueryException — a 500 instead of the sentence this method
        // exists to return. See PostCategoryController::destroy() for the same
        // bug explained at length.
        $events = $eventCategory->events()->withTrashed()->count();

        if ($events > 0) {
            return to_route('event-categories.index')->withErrors([
                'event_category' => __('Kategori agenda tidak dapat dihapus karena masih memiliki :count kegiatan. Pindahkan kegiatannya terlebih dahulu.', [
                    'count' => $events,
                ]),
            ]);
        }

        $eventCategory->delete();

        return to_route('event-categories.index')
            ->with('success', __('Kategori agenda berhasil dihapus.'));
    }
}
