<?php

namespace App\Http\Controllers\Profil;

use App\Concerns\ProfilFormMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\UpdateParishServiceAreaRequest;
use App\Http\Resources\Profil\ParishServiceAreaResource;
use App\Models\ParishServiceArea;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function __construct(private readonly AuditLogger $audit) {}

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
        DB::transaction(function () use ($request): void {
            $area = ParishServiceArea::current();
            $isNew = ! $area->exists;
            $before = $isNew ? [] : $this->audit->snapshot($area, 'parish_service_area');

            // fill()->save() for the same reason as the profile: update() is a
            // no-op before the singleton exists.
            $area->fill($request->validated())->save();

            if ($isNew) {
                $this->audit->created('parish_service_area', $area);
            } else {
                $this->audit->updated('parish_service_area', $area, $before);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Narasi wilayah pelayanan berhasil disimpan.')]);

        return to_route('parish-service-area.edit');
    }
}
