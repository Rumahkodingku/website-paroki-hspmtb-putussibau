<?php

namespace App\Http\Controllers\Livestream;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Livestream\PublicLivestreamResource;
use App\Models\Livestream;
use App\Models\LivestreamCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public livestreaming archive — /livestreaming.
 *
 * Every recording query starts from Livestream::published()->archiveReady().
 * That is the single definition of "a visitor may watch this recording": an
 * admin published it, the broadcast ended, and an admin verified the replay.
 * A draft, a scheduled stream, an unavailable video and a soft-deleted row are
 * excluded by the scopes rather than by a condition a later edit could forget.
 *
 * The active stream comes from Livestream::currentLive(), the same method the
 * homepage uses, so the two pages cannot disagree about what is live.
 *
 * There is no detail route: a card opens the modal player on this page.
 *
 * @see docs/DECISIONS.md D-101
 */
class PublicLivestreamController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $category = $request->string('kategori')->toString();
        $year = $request->string('tahun')->toString();

        $archives = Livestream::query()
            ->with(['location', 'category'])
            ->published()
            ->archiveReady()
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('livestreams.title', 'like', "%{$search}%")
                    ->orWhere('livestreams.description', 'like', "%{$search}%")
            ))
            ->when(
                $category !== '' && LivestreamCategory::query()->where('slug', $category)->exists(),
                fn (Builder $query) => $query->whereHas('category', fn (Builder $inner) => $inner->where('slug', $category))
            )
            ->when(
                ctype_digit($year),
                fn (Builder $query) => $query->whereYear('livestreams.recorded_at', (int) $year)
            )
            ->orderedForArchive()
            ->paginate(12)
            ->withQueryString();

        $activeStream = Livestream::currentLive();

        return Inertia::render('public/livestreaming', [
            'activeStream' => $activeStream === null
                ? null
                : PublicLivestreamResource::make($activeStream)->resolve(),
            'archives' => PublicLivestreamResource::collection($archives),
            'years' => $this->availableYears(),
            'categories' => LivestreamCategory::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (LivestreamCategory $category): array => [
                    'value' => $category->slug,
                    'label' => $category->name,
                ])
                ->all(),
            'filters' => [
                'search' => $search,
                'kategori' => $category,
                'tahun' => $year,
            ],
        ]);
    }

    /**
     * The distinct recording years among publicly playable recordings.
     *
     * @return list<int>
     */
    private function availableYears(): array
    {
        return array_values(
            Livestream::query()
                ->published()
                ->archiveReady()
                ->whereNotNull('recorded_at')
                ->selectRaw('distinct year(recorded_at) as year')
                ->orderByDesc('year')
                ->pluck('year')
                ->map(fn ($year): int => (int) $year)
                ->all()
        );
    }
}
