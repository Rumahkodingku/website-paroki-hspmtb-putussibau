<?php

namespace App\Http\Controllers\Profil;

use App\Actions\Profil\SetCurrentPastorAction;
use App\Concerns\ProfilFormMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\StorePastorAssignmentRequest;
use App\Http\Requests\Profil\UpdatePastorAssignmentRequest;
use App\Http\Resources\Profil\PastorAssignmentResource;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Riwayat penugasan seorang pastor.
 *
 * Satu pastor boleh punya banyak penugasan; hanya satu yang is_current di
 * seluruh tabel. Penyimpanan yang menandai current memakai
 * SetCurrentPastorAction agar transaksional.
 *
 * @see docs/DECISIONS.md D-52
 */
class PastorAssignmentController extends Controller
{
    use ProfilFormMeta;

    public function store(
        StorePastorAssignmentRequest $request,
        Pastor $pastor,
        SetCurrentPastorAction $setCurrent,
    ): RedirectResponse {
        $data = $request->validated();
        $data['pastor_id'] = $pastor->getKey();
        $wantsCurrent = (bool) $data['is_current'];
        // Create as not-current, then activate atomically if asked, so two
        // rows are never briefly current.
        $data['is_current'] = false;

        $assignment = PastorAssignment::create($data);

        if ($wantsCurrent) {
            $setCurrent->handle($assignment);
        }

        return to_route('pastors.edit', $pastor)
            ->with('success', __('Riwayat penugasan berhasil ditambahkan.'));
    }

    public function edit(Pastor $pastor, PastorAssignment $assignment): Response
    {
        Gate::authorize('update', $assignment);
        abort_unless($assignment->pastor_id === $pastor->getKey(), 404);

        return Inertia::render('admin/profil/pastor/penugasan-ubah', [
            'pastor' => ['id' => $pastor->getKey(), 'name' => $pastor->name],
            'assignment' => PastorAssignmentResource::make($assignment)->resolve(),
            ...$this->profilFormMeta(),
        ]);
    }

    public function update(
        UpdatePastorAssignmentRequest $request,
        Pastor $pastor,
        PastorAssignment $assignment,
        SetCurrentPastorAction $setCurrent,
    ): RedirectResponse {
        abort_unless($assignment->pastor_id === $pastor->getKey(), 404);

        $data = $request->validated();
        $wantsCurrent = (bool) $data['is_current'];
        $data['is_current'] = false;

        $assignment->update($data);

        if ($wantsCurrent) {
            $setCurrent->handle($assignment);
        }

        return to_route('pastors.edit', $pastor)
            ->with('success', __('Riwayat penugasan berhasil diperbarui.'));
    }

    public function activate(
        Pastor $pastor,
        PastorAssignment $assignment,
        SetCurrentPastorAction $setCurrent,
    ): RedirectResponse {
        Gate::authorize('update', $assignment);
        abort_unless($assignment->pastor_id === $pastor->getKey(), 404);

        $setCurrent->handle($assignment);

        return to_route('pastors.edit', $pastor)
            ->with('success', __(':name ditetapkan sebagai pastor yang bertugas.', [
                'name' => $pastor->name,
            ]));
    }

    public function destroy(Pastor $pastor, PastorAssignment $assignment): RedirectResponse
    {
        Gate::authorize('delete', $assignment);
        abort_unless($assignment->pastor_id === $pastor->getKey(), 404);

        $assignment->delete();

        return to_route('pastors.edit', $pastor)
            ->with('success', __('Riwayat penugasan berhasil dihapus.'));
    }
}
