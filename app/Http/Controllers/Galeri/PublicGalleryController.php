<?php

namespace App\Http\Controllers\Galeri;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Galeri\PublicGalleryCardResource;
use App\Http\Resources\Public\Galeri\PublicGalleryDetailResource;
use App\Models\GalleryAlbum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public gallery pages — /galeri and /galeri/{slug}
 *
 * Every query here starts from GalleryAlbum::published(). That is XC-E3, and it
 * is a scope rather than a repeated condition because the same filter has to
 * hold in the list, the detail and the route binding — and a second definition
 * of "public" is how one of them ends up not having it.
 *
 * Slug binding is `{gallery:slug}`, which GalleryAlbum::resolveRouteBinding
 * resolves through that same scope. A draft therefore answers 404 by not being
 * found, with no abort_if in this class that a later edit could forget.
 *
 * @see docs/DECISIONS.md D-65
 */
class PublicGalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $year = $request->string('tahun')->toString();

        $albums = GalleryAlbum::query()
            ->published()
            ->withCount('photos')
            ->with([
                'coverPhoto.media.variants',
                'photos' => fn ($query) => $query->ready()->with('media.variants'),
            ])
            ->when(
                ctype_digit($year),
                fn (Builder $query) => $query->whereYear('gallery_albums.event_date', (int) $year)
            )
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('public/galeri', [
            'albums' => PublicGalleryCardResource::collection($albums),
            'years' => $this->availableYears(),
            'filters' => [
                'tahun' => $year,
            ],
        ]);
    }

    public function show(GalleryAlbum $gallery): Response
    {
        $gallery->load([
            'coverPhoto.media.variants',
            'photos' => fn ($query) => $query->ready()->with('media.variants'),
        ]);
        // Count the same ready photos the page shows, not every row, so a photo
        // whose media never finished cannot make the count disagree with the grid.
        $gallery->loadCount(['photos' => fn ($query) => $query->ready()]);

        return Inertia::render('public/galeri/show', [
            'album' => PublicGalleryDetailResource::make($gallery)->resolve(),
        ]);
    }

    /**
     * The distinct event years among published albums, for the year filter.
     *
     * @return list<int>
     */
    private function availableYears(): array
    {
        return array_values(
            GalleryAlbum::query()
                ->published()
                ->whereNotNull('event_date')
                ->selectRaw('distinct year(event_date) as year')
                ->orderByDesc('year')
                ->pluck('year')
                ->map(fn ($year): int => (int) $year)
                ->all()
        );
    }
}
