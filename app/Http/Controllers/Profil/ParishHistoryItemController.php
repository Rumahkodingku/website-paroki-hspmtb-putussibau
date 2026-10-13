<?php

namespace App\Http\Controllers\Profil;

use App\Concerns\ProfilFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\StoreParishHistoryItemRequest;
use App\Http\Requests\Profil\UpdateParishHistoryItemRequest;
use App\Http\Resources\Profil\ParishHistoryItemResource;
use App\Http\Resources\Profil\ParishProfileResource;
use App\Models\ParishHistoryItem;
use App\Models\ParishProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sejarah Paroki — the narrative plus the timeline items.
 *
 * The narrative lives on the profile and is edited on /informasi; this page
 * manages the timeline items and shows the current narrative for context.
 *
 * @see docs/DECISIONS.md D-52
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
        )->paginate(15)->withQueryString();

        $profile = ParishProfile::current();

        return Inertia::render('admin/profil/sejarah/index', [
            'items' => ParishHistoryItemResource::collection($items),
            'profile' => ParishProfileResource::make(
                $profile->loadMissing(['logoMedia.variants', 'coverMedia.variants']),
            )->resolve(),
            'filters' => [
                'search' => $search,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->profilFormMeta(),
        ]);
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

        return to_route('parish-histories.index')
            ->with('success', __('Peristiwa sejarah berhasil ditambahkan.'));
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

        return to_route('parish-histories.index')
            ->with('success', __('Peristiwa sejarah berhasil diperbarui.'));
    }

    public function destroy(ParishHistoryItem $historyItem): RedirectResponse
    {
        Gate::authorize('delete', $historyItem);

        $historyItem->delete();

        return to_route('parish-histories.index')
            ->with('success', __('Peristiwa sejarah berhasil dihapus.'));
    }
}
