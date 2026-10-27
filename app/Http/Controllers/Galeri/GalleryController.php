<?php

namespace App\Http\Controllers\Galeri;

use App\Actions\Galeri\SaveGalleryAlbumAction;
use App\Concerns\GalleryFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Galeri\StoreGalleryAlbumRequest;
use App\Http\Requests\Galeri\UpdateGalleryAlbumRequest;
use App\Http\Resources\Galeri\GalleryAlbumListResource;
use App\Http\Resources\Galeri\GalleryAlbumResource;
use App\Models\GalleryAlbum;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Galeri — the admin album list and form.
 *
 * The list is server-paginated and server-sorted (SortsAdminList), the same shape
 * as Komunitas. The write path goes through SaveGalleryAlbumAction because a save
 * derives published_at and the audit columns, which is more than a controller
 * method should own.
 *
 * The photo manager is a distinct screen (D-5): the album form stays small and
 * the photo work — batch upload, reorder, cover, caption — gets its own room.
 *
 * @see docs/DECISIONS.md D-65
 */
class GalleryController extends Controller
{
    use GalleryFormMeta;

    public function __construct(private readonly AuditLogger $audit) {}

    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', GalleryAlbum::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $year = $request->string('tahun')->toString();

        $albums = GalleryAlbum::query()
            ->withCount('photos')
            ->with(['coverPhoto.media.variants', 'photos.media.variants'])
            ->when($search !== '', fn (Builder $query) => $query
                ->where('gallery_albums.title', 'like', "%{$search}%"))
            ->when(
                in_array($status, ['published', 'draft'], true),
                fn (Builder $query) => $query->where('gallery_albums.is_published', $status === 'published')
            )
            ->when(
                ctype_digit($year),
                fn (Builder $query) => $query->whereYear('gallery_albums.event_date', (int) $year)
            );

        $albums = $this->applyAdminSort(
            $albums,
            $request,
            [
                'title' => 'gallery_albums.title',
                'event_date' => 'gallery_albums.event_date',
                'is_published' => 'gallery_albums.is_published',
                'updated_at' => 'gallery_albums.updated_at',
            ],
            [['gallery_albums.updated_at', 'desc']],
        )->paginate($this->perPage($request))->withQueryString();

        return Inertia::render('admin/galeri', [
            'albums' => GalleryAlbumListResource::collection($albums),
            'years' => $this->availableYears(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'tahun' => $year,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->galleryFormMeta(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', GalleryAlbum::class);

        return Inertia::render('admin/galeri/tambah', [
            ...$this->galleryFormMeta(),
        ]);
    }

    public function store(StoreGalleryAlbumRequest $request, SaveGalleryAlbumAction $action): RedirectResponse
    {
        $album = new GalleryAlbum;
        $action->handle($album, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('galeri.flash.album_created')]);

        return to_route('galleries.photos', $album);
    }

    public function edit(GalleryAlbum $gallery): Response
    {
        Gate::authorize('update', $gallery);

        $gallery->load([
            'coverPhoto.media.variants',
            'photos.media.variants',
        ]);
        $gallery->loadCount('photos');

        return Inertia::render('admin/galeri/ubah', [
            'album' => GalleryAlbumResource::make($gallery)->resolve(),
            ...$this->galleryFormMeta(),
        ]);
    }

    public function update(UpdateGalleryAlbumRequest $request, GalleryAlbum $gallery, SaveGalleryAlbumAction $action): RedirectResponse
    {
        $action->handle($gallery, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('galeri.flash.album_updated')]);

        return to_route('galleries.edit', $gallery);
    }

    public function destroy(GalleryAlbum $gallery): RedirectResponse
    {
        Gate::authorize('delete', $gallery);

        DB::transaction(function () use ($gallery): void {
            $before = $this->audit->snapshot($gallery, 'galleries');
            $gallery->delete();
            $this->audit->deleted('galleries', $gallery, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('galeri.flash.album_deleted')]);

        return to_route('galleries.index');
    }

    /**
     * The photo manager for one album.
     *
     * Gated on `update`, the same ability that guards every photo mutation, so a
     * user who may not edit the album cannot open the screen that edits it.
     */
    public function photos(GalleryAlbum $gallery): Response
    {
        Gate::authorize('update', $gallery);

        $gallery->load([
            'coverPhoto.media.variants',
            'photos.media.variants',
        ]);
        $gallery->loadCount('photos');

        return Inertia::render('admin/galeri/foto', [
            'album' => GalleryAlbumResource::make($gallery)->resolve(),
            ...$this->galleryFormMeta(),
        ]);
    }

    /**
     * The distinct event years, for the year filter.
     *
     * @return list<int>
     */
    private function availableYears(): array
    {
        return array_values(
            GalleryAlbum::query()
                ->whereNotNull('event_date')
                ->selectRaw('distinct year(event_date) as year')
                ->orderByDesc('year')
                ->pluck('year')
                ->map(fn ($year): int => (int) $year)
                ->all()
        );
    }
}
