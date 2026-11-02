<?php

namespace App\Http\Controllers\Livestream;

use App\Actions\Livestream\EndLivestreamAction;
use App\Actions\Livestream\MarkLivestreamUnavailableAction;
use App\Actions\Livestream\SaveLivestreamAction;
use App\Actions\Livestream\StartLivestreamAction;
use App\Concerns\LivestreamFormMeta;
use App\Concerns\SortsAdminList;
use App\Enums\LivestreamBroadcastStatus;
use App\Enums\LivestreamEditorialStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Livestream\StoreLivestreamRequest;
use App\Http\Requests\Livestream\UpdateLivestreamRequest;
use App\Http\Resources\Livestream\LivestreamResource;
use App\Http\Resources\MassSchedule\LocationOptionResource;
use App\Models\Livestream;
use App\Models\LivestreamCategory;
use App\Models\Location;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Livestreaming — the admin list and form.
 *
 * The list is server-paginated and server-sorted (SortsAdminList), the same
 * shape as Galeri. The write path goes through SaveLivestreamAction because a
 * save derives the video id, published_at and the audit columns, which is more
 * than a controller method should own.
 *
 * Starting/ending/marking unavailable are separate POST actions because they
 * change the operational state, not the editorial record. They share
 * `livestreams.update` through LivestreamPolicy.
 *
 * @see docs/DECISIONS.md D-101
 */
class LivestreamController extends Controller
{
    use LivestreamFormMeta;
    use SortsAdminList;

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Livestream::class);

        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $broadcastStatus = $request->string('broadcast_status')->toString();
        $category = $request->string('kategori')->toString();

        $livestreams = Livestream::query()
            ->with(['location', 'category'])
            ->when($search !== '', fn (Builder $query) => $query
                ->where('livestreams.title', 'like', "%{$search}%"))
            ->when(
                in_array($status, LivestreamEditorialStatus::values(), true),
                fn (Builder $query) => $query->where('livestreams.status', $status)
            )
            ->when(
                in_array($broadcastStatus, LivestreamBroadcastStatus::values(), true),
                fn (Builder $query) => $query->where('livestreams.broadcast_status', $broadcastStatus)
            )
            ->when(
                $category !== '' && ctype_digit($category) && LivestreamCategory::query()->whereKey((int) $category)->exists(),
                fn (Builder $query) => $query->where('livestreams.livestream_category_id', (int) $category)
            );

        $livestreams = $this->applyAdminSort(
            $livestreams,
            $request,
            [
                'title' => 'livestreams.title',
                'status' => 'livestreams.status',
                'broadcast_status' => 'livestreams.broadcast_status',
                'scheduled_at' => 'livestreams.scheduled_at',
                'recorded_at' => 'livestreams.recorded_at',
                'updated_at' => 'livestreams.updated_at',
            ],
            [['livestreams.updated_at', 'desc']],
        )->paginate($this->perPage($request))->withQueryString();

        return Inertia::render('admin/livestreaming', [
            'livestreams' => LivestreamResource::collection($livestreams),
            'activeLive' => Livestream::currentLive()?->only(['id', 'title']),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'broadcast_status' => $broadcastStatus,
                'kategori' => $category,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->livestreamFormMeta(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Livestream::class);

        return Inertia::render('admin/livestreaming/tambah', [
            'locations' => array_values(
                LocationOptionResource::collection($this->activeLocations())->resolve()
            ),
            ...$this->livestreamFormMeta(),
        ]);
    }

    public function store(StoreLivestreamRequest $request, SaveLivestreamAction $action): RedirectResponse
    {
        $livestream = $action->handle(new Livestream, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('livestreaming.flash.created')]);

        return to_route('livestreams.edit', $livestream);
    }

    public function edit(Livestream $livestream): Response
    {
        Gate::authorize('update', $livestream);

        $livestream->load('location');

        return Inertia::render('admin/livestreaming/ubah', [
            'livestream' => LivestreamResource::make($livestream)->resolve(),
            'locations' => array_values(
                LocationOptionResource::collection($this->activeLocations())->resolve()
            ),
            ...$this->livestreamFormMeta(),
        ]);
    }

    public function update(
        UpdateLivestreamRequest $request,
        Livestream $livestream,
        SaveLivestreamAction $action,
    ): RedirectResponse {
        $action->handle($livestream, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('livestreaming.flash.updated')]);

        return to_route('livestreams.edit', $livestream);
    }

    public function destroy(Livestream $livestream): RedirectResponse
    {
        Gate::authorize('delete', $livestream);

        DB::transaction(function () use ($livestream): void {
            $before = $this->audit->snapshot($livestream, 'livestreams');

            // Clear the slot before the soft delete. A trashed row still owns
            // the unique index, and would block the next stream from starting.
            if ($livestream->live_slot !== null) {
                $livestream->forceFill(['live_slot' => null])->save();
            }

            $livestream->delete();
            $this->audit->deleted('livestreams', $livestream, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('livestreaming.flash.deleted')]);

        return to_route('livestreams.index');
    }

    public function start(Livestream $livestream, StartLivestreamAction $action): RedirectResponse
    {
        Gate::authorize('update', $livestream);

        $action->handle($livestream);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('livestreaming.flash.started')]);

        return back();
    }

    public function end(Livestream $livestream, EndLivestreamAction $action): RedirectResponse
    {
        Gate::authorize('update', $livestream);

        $action->handle($livestream);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('livestreaming.flash.ended')]);

        return back();
    }

    public function unavailable(
        Livestream $livestream,
        MarkLivestreamUnavailableAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $livestream);

        $action->handle($livestream);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('livestreaming.flash.unavailable')]);

        return back();
    }

    /**
     * The locations an admin may choose.
     *
     * Active only, the same rule as the mass schedule forms: a deactivated
     * location is no longer a valid place for new content. A record that still
     * points at one keeps its name in the public archive.
     *
     * @return Collection<int, Location>
     */
    private function activeLocations(): Collection
    {
        return Location::query()->active()->orderBy('name')->get();
    }
}
