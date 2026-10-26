<?php

namespace App\Http\Controllers\Profil;

use App\Concerns\ProfilFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\StorePastorRequest;
use App\Http\Requests\Profil\UpdatePastorRequest;
use App\Http\Resources\Profil\PastorResource;
use App\Models\Pastor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pastor Paroki — identity rows.
 *
 * Assignment history lives on PastorAssignmentController; this page shows each
 * pastor with the assignment currently in office.
 *
 * @see docs/DECISIONS.md D-52
 */
class PastorController extends Controller
{
    use ProfilFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Pastor::class);

        $search = $request->string('search')->toString();

        $pastors = Pastor::query()
            ->with(['currentAssignment', 'photoMedia.variants'])
            ->withCount('assignments')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%"));

        $pastors = $this->applyAdminSort(
            $pastors,
            $request,
            [
                'name' => 'pastors.name',
                'sort_order' => 'pastors.sort_order',
            ],
            [['pastors.sort_order', 'asc'], ['pastors.name', 'asc']],
        )->paginate(15)->withQueryString();

        return Inertia::render('admin/profil/pastor/index', [
            'pastors' => PastorResource::collection($pastors),
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
        Gate::authorize('create', Pastor::class);

        return Inertia::render('admin/profil/pastor/tambah', [
            ...$this->profilFormMeta(),
        ]);
    }

    public function store(StorePastorRequest $request): RedirectResponse
    {
        $pastor = Pastor::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Data pastor berhasil ditambahkan. Tambahkan riwayat penugasannya.')]);

        return to_route('pastors.edit', $pastor);
    }

    public function edit(Pastor $pastor): Response
    {
        Gate::authorize('update', $pastor);

        return Inertia::render('admin/profil/pastor/ubah', [
            'pastor' => PastorResource::make(
                $pastor->loadMissing(['photoMedia.variants', 'currentAssignment'])
                    ->load(['assignments' => fn ($query) => $query->orderByDesc('is_current')->orderByDesc('start_year')]),
            )->resolve(),
            ...$this->profilFormMeta(),
        ]);
    }

    public function update(UpdatePastorRequest $request, Pastor $pastor): RedirectResponse
    {
        $pastor->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Data pastor berhasil diperbarui.')]);

        return to_route('pastors.edit', $pastor);
    }

    public function destroy(Pastor $pastor): RedirectResponse
    {
        Gate::authorize('delete', $pastor);

        // Assignments cascade at the database level; deleting a pastor is a
        // deliberate act and the confirm dialog names the history that goes
        // with it.
        $pastor->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Data pastor berhasil dihapus.')]);

        return to_route('pastors.index');
    }
}
