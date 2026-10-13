<?php

namespace App\Http\Controllers\Profil;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Profil\PublicBoardPeriodResource;
use App\Http\Resources\Public\Profil\PublicParishHistoryItemResource;
use App\Http\Resources\Public\Profil\PublicParishProfileResource;
use App\Http\Resources\Public\Profil\PublicParishServiceAreaResource;
use App\Http\Resources\Public\Profil\PublicPastorAssignmentResource;
use App\Http\Resources\Public\Profil\PublicPastorResource;
use App\Models\ParishBoardPeriod;
use App\Models\ParishHistoryItem;
use App\Models\ParishProfile;
use App\Models\ParishServiceArea;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profil Paroki — the five public pages.
 *
 * Every query starts from a `published()` scope, so a draft or unpublished row
 * cannot reach a visitor even by editing a URL. Empty state is deliberate: with
 * no published profile the page renders its own message rather than invented
 * content (roadmap 6.1).
 *
 * @see docs/DECISIONS.md D-52
 */
class PublicParishProfileController extends Controller
{
    public function index(): Response
    {
        $profile = ParishProfile::published()
            ->with(['logoMedia.variants', 'coverMedia.variants'])
            ->first();

        return Inertia::render('public/profil', [
            'profile' => $profile === null
                ? null
                : PublicParishProfileResource::make($profile)->resolve(),
            // Availability drives which navigation cards the page shows, so an
            // empty section is not advertised as ready.
            'sections' => [
                'sejarah' => ParishHistoryItem::published()->exists(),
                'wilayah' => ParishServiceArea::published()->exists(),
                'pastor' => Pastor::published()->exists(),
                'struktur' => ParishBoardPeriod::published()->exists(),
            ],
        ]);
    }

    public function sejarah(): Response
    {
        $profile = ParishProfile::published()->first();
        $items = ParishHistoryItem::published()
            ->with('media.variants')
            ->ordered()
            ->get();

        return Inertia::render('public/profil/sejarah', [
            'profile' => $profile === null
                ? null
                : PublicParishProfileResource::make($profile)->resolve(),
            'items' => array_values(PublicParishHistoryItemResource::collection($items)->resolve()),
        ]);
    }

    public function wilayah(): Response
    {
        $area = ParishServiceArea::published()->with('mapMedia.variants')->first();

        return Inertia::render('public/profil/wilayah', [
            'area' => $area === null
                ? null
                : PublicParishServiceAreaResource::make($area)->resolve(),
        ]);
    }

    public function pastor(): Response
    {
        $current = Pastor::published()
            ->whereHas('currentAssignment')
            ->with(['photoMedia.variants', 'currentAssignment'])
            ->ordered()
            ->get();

        // History: assignments that are no longer current, for published
        // pastors only. Most recent first so the list reads backwards in time.
        $history = PastorAssignment::query()
            ->where('is_current', false)
            ->whereHas('pastor', fn ($query) => $query->where('is_published', true))
            ->with('pastor')
            ->orderByDesc('end_year')
            ->orderByDesc('start_year')
            ->get();

        return Inertia::render('public/profil/pastor', [
            'current' => array_values(PublicPastorResource::collection($current)->resolve()),
            'history' => array_values(PublicPastorAssignmentResource::collection($history)->resolve()),
        ]);
    }

    public function struktur(): Response
    {
        $periods = ParishBoardPeriod::published()
            ->with([
                'members' => fn ($query) => $query->published(),
                'members.photoMedia.variants',
            ])
            ->ordered()
            ->get();

        return Inertia::render('public/profil/struktur', [
            'periods' => array_values(PublicBoardPeriodResource::collection($periods)->resolve()),
        ]);
    }
}
