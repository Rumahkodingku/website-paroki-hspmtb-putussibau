<?php

namespace App\Http\Controllers\Profil;

use App\Concerns\ProfilFormMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\UpdateParishProfileRequest;
use App\Http\Resources\Profil\ParishProfileResource;
use App\Models\ParishProfile;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function __construct(private readonly AuditLogger $audit) {}

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
        DB::transaction(function () use ($request): void {
            $profile = ParishProfile::current();
            $isNew = ! $profile->exists;
            $before = $isNew ? [] : $this->audit->snapshot($profile, 'parish_profile');

            // fill()->save() rather than update(): update() is a no-op before the
            // singleton exists.
            $profile->fill($request->validated())->save();

            if ($isNew) {
                $this->audit->created('parish_profile', $profile);
            } else {
                $this->audit->updated('parish_profile', $profile, $before);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Informasi profil berhasil disimpan.')]);

        return to_route('parish-profile.edit');
    }
}
