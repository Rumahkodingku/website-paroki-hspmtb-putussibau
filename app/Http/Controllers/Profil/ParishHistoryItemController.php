<?php

namespace App\Http\Controllers\Profil;

use App\Concerns\ProfilFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\StoreParishHistoryItemRequest;
use App\Http\Requests\Profil\UpdateParishHistoryItemRequest;
use App\Http\Requests\Profil\UpdateParishHistoryNarrativeRequest;
use App\Http\Resources\Profil\ParishHistoryItemResource;
use App\Models\ParishHistoryItem;
use App\Models\ParishHistoryNarrative;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sejarah Paroki — the narrative plus the timeline items.
 *
 * The narrative is a singleton edited on this page; the timeline items are
 * managed below it. The narrative used to live on the removed Informasi Profil
 * module and moved here in D-69.
 *
 * @see docs/DECISIONS.md D-69
 */
class ParishHistoryItemController extends Controller
{
    use ProfilFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ParishHistoryItem::class);

        $search = $request->string('search')->toString();

        $items = ParishHistoryItem::query()
            ->when($search !== '', fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%"));

        $items = $this->applyAdminSort(
            $items,
            $request,
            [
                'title' => 'parish_history_items.title',
                'year' => 'parish_history_items.year',
                'date' => 'parish_history_items.date',
                'sort_order' => 'parish_history_items.sort_order',
            ],
            [
                ['parish_history_items.sort_order', 'asc'],
                ['parish_history_items.year', 'asc'],
            ],
        )->paginate($this->perPage($request))->withQueryString();

        $narrative = ParishHistoryNarrative::current();

        return Inertia::render('admin/profil/sejarah/index', [
            'items' => ParishHistoryItemResource::collection($items),
            'narrative' => $narrative->narrative,
            'filters' => [
                'search' => $search,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->profilFormMeta(),
        ]);
    }

    /**
     * Save the history narrative singleton.
     *
     * The narrative is sanitized in the form request before it reaches here.
     */
    public function updateNarrative(UpdateParishHistoryNarrativeRequest $request): RedirectResponse
    {
        ParishHistoryNarrative::current()
            ->fill(['narrative' => $request->validated('narrative')])
            ->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Narasi sejarah berhasil disimpan.')]);

        return to_route('parish-histories.index');
    }

    public function create(): Response
    {
        Gate::authorize('create', ParishHistoryItem::class);

        return Inertia::render('admin/profil/sejarah/tambah', [
            ...$this->profilFormMeta(),
        ]);
    }

    public function store(StoreParishHistoryItemRequest $request): RedirectResponse
    {
        ParishHistoryItem::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Peristiwa sejarah berhasil ditambahkan.')]);

        return to_route('parish-histories.index');
    }

    public function edit(ParishHistoryItem $historyItem): Response
    {
        Gate::authorize('update', $historyItem);

        return Inertia::render('admin/profil/sejarah/ubah', [
            'item' => ParishHistoryItemResource::make(
                $historyItem->loadMissing('media.variants'),
            )->resolve(),
            ...$this->profilFormMeta(),
        ]);
    }

    public function update(UpdateParishHistoryItemRequest $request, ParishHistoryItem $historyItem): RedirectResponse
    {
        $historyItem->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Peristiwa sejarah berhasil diperbarui.')]);

        return to_route('parish-histories.index');
    }

    public function destroy(ParishHistoryItem $historyItem): RedirectResponse
    {
        Gate::authorize('delete', $historyItem);

        $historyItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Peristiwa sejarah berhasil dihapus.')]);

        return to_route('parish-histories.index');
    }
}
