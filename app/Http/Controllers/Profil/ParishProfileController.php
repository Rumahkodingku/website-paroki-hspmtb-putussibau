<?php

namespace App\Http\Controllers\Profil;

use App\Concerns\ProfilFormMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\UpdateParishProfileRequest;
use App\Http\Resources\Profil\ParishProfileResource;
use App\Models\ParishProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Informasi Profil — the editorial content of /profil.
 *
 * The content moved back out of site settings in D-78; identity and contact
 * stay there. This page is the module root.
 *
 * @see docs/DECISIONS.md D-78
 */
class ParishProfileController extends Controller
{
    use ProfilFormMeta;

    public function edit(): Response
    {
        Gate::authorize('viewAny', ParishProfile::class);

        $profile = ParishProfile::current();

        return Inertia::render('admin/profil/informasi/index', [
            'profile' => ParishProfileResource::make(
                $profile->loadMissing('coverMedia.variants'),
            )->resolve(),
            ...$this->profilFormMeta(),
        ]);
    }

    public function update(UpdateParishProfileRequest $request): RedirectResponse
    {
        // fill()->save() rather than update(): update() is a no-op before the
        // singleton exists.
        ParishProfile::current()
            ->fill($request->validated())
            ->save();

        return to_route('parish-profile.edit')
            ->with('success', __('Informasi profil berhasil disimpan.'));
    }
}
