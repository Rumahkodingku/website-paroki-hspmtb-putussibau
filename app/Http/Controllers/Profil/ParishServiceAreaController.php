<?php

namespace App\Http\Controllers\Profil;

use App\Concerns\ProfilFormMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\UpdateParishServiceAreaRequest;
use App\Http\Resources\Profil\ParishServiceAreaResource;
use App\Models\ParishServiceArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Wilayah Pelayanan — the service-area narrative singleton.
 *
 * No hierarchy of wilayah/stasi/lingkungan: that domain belongs to the
 * Communities module in Phase 7 and is only displayed there (D-52).
 *
 * @see docs/DECISIONS.md D-52
 */
class ParishServiceAreaController extends Controller
{
    use ProfilFormMeta;

    public function edit(): Response
    {
        Gate::authorize('viewAny', ParishServiceArea::class);

        $area = ParishServiceArea::current();

        return Inertia::render('admin/profil/wilayah/index', [
            'area' => ParishServiceAreaResource::make(
                $area->loadMissing('mapMedia.variants'),
            )->resolve(),
            ...$this->profilFormMeta(),
        ]);
    }

    public function update(UpdateParishServiceAreaRequest $request): RedirectResponse
    {
        $area = ParishServiceArea::current();
        // fill()->save() for the same reason as the profile: update() is a
        // no-op before the singleton exists.
        $area->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Narasi wilayah pelayanan berhasil disimpan.')]);

        return to_route('parish-service-area.edit');
    }
}
