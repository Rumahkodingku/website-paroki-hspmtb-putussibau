<?php

namespace App\Http\Controllers\Profil;

use App\Concerns\ProfilFormMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\StoreParishBoardMemberRequest;
use App\Http\Requests\Profil\UpdateParishBoardMemberRequest;
use App\Http\Resources\Profil\ParishBoardMemberResource;
use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Anggota kepengurusan pada satu periode.
 *
 * @see docs/DECISIONS.md D-52
 */
class ParishBoardMemberController extends Controller
{
    use ProfilFormMeta;

    public function __construct(private readonly AuditLogger $audit) {}

    public function create(ParishBoardPeriod $period): Response
    {
        Gate::authorize('create', ParishBoardMember::class);

        return Inertia::render('admin/profil/struktur/anggota-tambah', [
            'period' => ['id' => $period->getKey(), 'name' => $period->name],
            ...$this->profilFormMeta(),
        ]);
    }

    public function store(StoreParishBoardMemberRequest $request, ParishBoardPeriod $period): RedirectResponse
    {
        $data = $request->validated();
        $data['parish_board_period_id'] = $period->getKey();

        DB::transaction(function () use ($data): void {
            $member = ParishBoardMember::query()->create($data);
            $this->audit->created('parish_board_members', $member);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Anggota kepengurusan berhasil ditambahkan.')]);

        return to_route('board-periods.index', ['periode' => $period->getKey()]);
    }

    public function edit(ParishBoardPeriod $period, ParishBoardMember $member): Response
    {
        Gate::authorize('update', $member);
        abort_unless($member->parish_board_period_id === $period->getKey(), 404);

        return Inertia::render('admin/profil/struktur/anggota-ubah', [
            'period' => ['id' => $period->getKey(), 'name' => $period->name],
            'member' => ParishBoardMemberResource::make(
                $member->loadMissing('photoMedia.variants'),
            )->resolve(),
            ...$this->profilFormMeta(),
        ]);
    }

    public function update(
        UpdateParishBoardMemberRequest $request,
        ParishBoardPeriod $period,
        ParishBoardMember $member,
    ): RedirectResponse {
        abort_unless($member->parish_board_period_id === $period->getKey(), 404);

        DB::transaction(function () use ($request, $member): void {
            $before = $this->audit->snapshot($member, 'parish_board_members');
            $member->update($request->validated());
            $this->audit->updated('parish_board_members', $member, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Anggota kepengurusan berhasil diperbarui.')]);

        return to_route('board-periods.index', ['periode' => $period->getKey()]);
    }

    public function destroy(ParishBoardPeriod $period, ParishBoardMember $member): RedirectResponse
    {
        Gate::authorize('delete', $member);
        abort_unless($member->parish_board_period_id === $period->getKey(), 404);

        DB::transaction(function () use ($member): void {
            $before = $this->audit->snapshot($member, 'parish_board_members');
            $member->delete();
            $this->audit->deleted('parish_board_members', $member, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Anggota kepengurusan berhasil dihapus.')]);

        return to_route('board-periods.index', ['periode' => $period->getKey()]);
    }
}
