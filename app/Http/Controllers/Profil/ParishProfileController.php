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
 * Profil Paroki — the main information form.
 *
 * The profile is a singleton: there is no list, no create and no delete, only
 * edit and update. `/admin/profil` redirects here (routes/web.php).
 *
 * @see docs/DECISIONS.md D-52
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
                $profile->loadMissing(['logoMedia.variants', 'coverMedia.variants']),
            )->resolve(),
            ...$this->profilFormMeta(),
        ]);
    }

    public function update(UpdateParishProfileRequest $request): RedirectResponse
    {
        $profile = ParishProfile::current();

        // fill()->save(), not update(): Eloquent's update() is a no-op on an
        // unsaved model (`! $this->exists`), so the first save would silently
        // do nothing. save() inserts when the instance is new and updates when
        // it is not, so the singleton is created once and edited thereafter.
        $profile->fill($request->validated())->save();

        return to_route('parish-profile.edit')
            ->with('success', __('Informasi profil berhasil disimpan.'));
    }
}
