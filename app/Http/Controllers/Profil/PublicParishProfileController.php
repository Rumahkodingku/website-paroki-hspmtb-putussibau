<?php

namespace App\Http\Controllers\Profil;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Profil\PublicBoardPeriodResource;
use App\Http\Resources\Public\Profil\PublicParishHistoryItemResource;
use App\Http\Resources\Public\Profil\PublicParishServiceAreaResource;
use App\Http\Resources\Public\Profil\PublicPastorAssignmentResource;
use App\Http\Resources\Public\Profil\PublicPastorResource;
use App\Models\ParishBoardPeriod;
use App\Models\ParishHistoryItem;
use App\Models\ParishHistoryNarrative;
use App\Models\ParishServiceArea;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profil Paroki — the public sub-pages.
 *
 * The Informasi Profil summary page was removed (D-69): identity now lives in
 * site settings and is rendered by the shared layout. What remains here are the
 * four section pages, each starting from a `published()` scope so a draft row
 * cannot reach a visitor even by editing a URL.
 *
 * @see docs/DECISIONS.md D-69
 */
class PublicParishProfileController extends Controller
{
    public function sejarah(): Response
    {
        $narrative = ParishHistoryNarrative::current();
        $items = ParishHistoryItem::published()
            ->with('media.variants')
            ->ordered()
            ->get();

        return Inertia::render('public/profil/sejarah', [
            'narrative' => $narrative->narrative,
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
            ->with('pastor.photoMedia.variants')
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
