<?php

namespace App\Http\Controllers\Profil;

use App\Actions\Profil\ActivateBoardPeriodAction;
use App\Concerns\ProfilFormMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\StoreParishBoardPeriodRequest;
use App\Http\Requests\Profil\UpdateParishBoardPeriodRequest;
use App\Http\Resources\Profil\ParishBoardPeriodResource;
use App\Models\ParishBoardPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Struktur Kepengurusan — periode dan anggotanya.
 *
 * Index menampilkan seluruh periode beserta periode terpilih (?periode=ID) dan
 * anggotanya. Menghapus periode menghapus anggotanya (CASCADE) dan dialog
 * konfirmasi menyebut jumlahnya.
 *
 * @see docs/DECISIONS.md D-52
 */
class ParishBoardPeriodController extends Controller
{
    use ProfilFormMeta;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ParishBoardPeriod::class);

        $periods = ParishBoardPeriod::query()
            ->withCount('members')
            ->orderByDesc('start_year')
            ->orderByDesc('id')
            ->get();

        // The period the member list is showing: the requested one, else the
        // active one, else the newest. Never null unless there are no periods.
        $selected = $periods->firstWhere('id', $request->integer('periode'))
            ?? $periods->firstWhere('is_active', true)
            ?? $periods->first();

        if ($selected !== null) {
            // Variants are eager loaded because the member photo URL is resolved
            // from the media row; without it the column renders nothing.
            $selected->load(['members.photoMedia.variants']);
        }

        return Inertia::render('admin/profil/struktur/index', [
            'periods' => array_values(ParishBoardPeriodResource::collection($periods)->resolve()),
            'selected' => $selected === null
                ? null
                : ParishBoardPeriodResource::make($selected)->resolve(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ParishBoardPeriod::class);

        return Inertia::render('admin/profil/struktur/periode-tambah', [
            ...$this->profilFormMeta(),
        ]);
    }

    public function store(StoreParishBoardPeriodRequest $request, ActivateBoardPeriodAction $activate): RedirectResponse
    {
        $data = $request->validated();
        $wantsActive = (bool) $data['is_active'];
        $data['is_active'] = false;

        $period = ParishBoardPeriod::create($data);

        if ($wantsActive) {
            $activate->handle($period);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Periode kepengurusan berhasil ditambahkan.')]);

        return to_route('board-periods.index');
    }

    public function edit(ParishBoardPeriod $period): Response
    {
        Gate::authorize('update', $period);

        return Inertia::render('admin/profil/struktur/periode-ubah', [
            'period' => ParishBoardPeriodResource::make(
                $period->load(['members.photoMedia.variants']),
            )->resolve(),
            ...$this->profilFormMeta(),
        ]);
    }

    public function update(
        UpdateParishBoardPeriodRequest $request,
        ParishBoardPeriod $period,
        ActivateBoardPeriodAction $activate,
    ): RedirectResponse {
        $data = $request->validated();
        $wantsActive = (bool) $data['is_active'];
        $data['is_active'] = false;

        $period->update($data);

        if ($wantsActive) {
            $activate->handle($period);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Periode kepengurusan berhasil diperbarui.')]);

        return to_route('board-periods.index');
    }

    public function activate(ParishBoardPeriod $period, ActivateBoardPeriodAction $activate): RedirectResponse
    {
        Gate::authorize('update', $period);

        $activate->handle($period);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name ditetapkan sebagai periode aktif.', [
            'name' => $period->name,
        ])]);

        return to_route('board-periods.index');
    }

    public function destroy(ParishBoardPeriod $period): RedirectResponse
    {
        Gate::authorize('delete', $period);

        $members = $period->members()->count();

        // CASCADE at the database, but the message is what tells an admin what
        // they are about to lose.
        $period->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => $members === 0
            ? __('Periode kepengurusan berhasil dihapus.')
            : __('Periode kepengurusan beserta :count anggotanya berhasil dihapus.', [
                'count' => $members,
            ])]);

        return to_route('board-periods.index');
    }
}
