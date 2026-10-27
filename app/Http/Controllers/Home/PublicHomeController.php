<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Agenda\PublicEventResource;
use App\Http\Resources\Public\Galeri\PublicGalleryTeaserResource;
use App\Http\Resources\Public\MassSchedule\NextMassResource;
use App\Http\Resources\Public\News\PublicPostResource;
use App\Http\Resources\Public\Pelayanan\PublicServiceCardResource;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\Media;
use App\Models\Post;
use App\Models\Service;
use App\Services\NextMassResolver;
use App\Services\SiteSettingsService;
use App\Support\HomepageIllustration;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public homepage — the aggregation page PRD 7.1 describes.
 *
 * There is no homepage table and no second source of truth. Every block is a
 * query against the module that already owns the content, shaped by that
 * module's public resource. The controller is the only place those queries are
 * put side by side.
 *
 * Two rules are load-bearing:
 *
 * 1. A section whose setting is off is not queried at all. Sending the rows and
 *    hiding them in React would put content in the page source that the visitor
 *    is not meant to see.
 *
 * 2. Every list is a plain array, not a resource collection with a `data`
 *    envelope. These lists are never paginated, and D-39 records how a
 *    non-paginated collection arrives as {data: [...]} otherwise.
 *
 * @see docs/plan-implementation/plan-phase-9a-homepage.md
 * @see docs/DECISIONS.md D-70
 */
class PublicHomeController extends Controller
{
    public function __construct(
        private readonly NextMassResolver $resolver,
        private readonly SiteSettingsService $settings,
    ) {}

    public function __invoke(): Response
    {
        $newsLimit = $this->intSetting('home_news_limit', 3);
        $eventsLimit = $this->intSetting('home_events_limit', 3);
        $galleryLimit = $this->intSetting('home_gallery_limit', 6);

        $showDevotion = $this->boolSetting('home_show_devotion');
        $showServices = $this->boolSetting('home_show_services');
        $showGallery = $this->boolSetting('home_show_gallery');

        $nextMass = $this->resolver->resolve();
        $galleryPhotos = $showGallery ? $this->gallery($galleryLimit) : [];

        return Inertia::render('public/beranda', [
            'nextMass' => $nextMass === null
                ? null
                : NextMassResource::make($nextMass)->resolve(),
            'news' => $this->news($newsLimit),
            'events' => $this->events($eventsLimit),
            'devotion' => $showDevotion ? $this->devotion() : null,
            'services' => $showServices ? $this->services() : [],
            'galleryPhotos' => $galleryPhotos,
            'galleryIllustrations' => $showGallery
                ? $this->galleryIllustrations($galleryPhotos)
                : [],
            'hero' => $this->hero(),
            'sections' => [
                'devotion' => $showDevotion,
                'services' => $showServices,
                'gallery' => $showGallery,
            ],
        ]);
    }

    /**
     * The hero's text and image, resolved once on the server.
     *
     * The parish's uploaded image wins. The fallback is a committed asset, so a
     * fresh installation still opens with the approved photograph instead of a
     * blank panel. The screenshot the plan worked from was supplied by the
     * project owner; no second photograph is fetched from the network.
     *
     * @return array{title: string|null, description: string|null, image: string, imageAlt: string, ogImage: string, width: int, height: int, motto: string|null}
     */
    private function hero(): array
    {
        $mediaId = $this->stringSetting('home_hero_image');
        $media = $mediaId === null
            ? null
            : Media::query()->with('variants')->find((int) $mediaId);

        $mediaUrl = $media?->url('large') ?? $media?->url('medium');

        // The media dimensions only describe the image being rendered. When the
        // fallback is used, the fallback's own dimensions are the honest pair.
        $width = 1672;
        $height = 941;

        if ($mediaUrl !== null && $media !== null) {
            $width = $media->width ?? 1672;
            $height = $media->height ?? 941;
        }

        return [
            'title' => $this->stringSetting('parish_name'),
            'description' => $this->stringSetting('short_description'),
            'image' => $mediaUrl ?? (string) config('homepage.fallback_hero_image'),
            'imageAlt' => $this->stringSetting('home_hero_image_alt')
                ?? (string) config('homepage.fallback_hero_image_alt'),
            'ogImage' => $this->stringSetting('seo_default_og_image')
                ?? (string) config('homepage.fallback_hero_og_image'),
            'width' => $width,
            'height' => $height,
            'motto' => $this->stringSetting('motto_verse'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function news(int $limit): array
    {
        $items = array_values(PublicPostResource::collection(
            Post::query()
                ->published()
                ->with('category')
                ->orderByDesc('published_at')
                // published_at is not unique, so id makes the order total and
                // the same three articles cannot swap between requests.
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
        )->resolve());

        return array_map(function (array $item): array {
            if ($item['image'] !== null) {
                return $item;
            }

            $illustration = HomepageIllustration::for('post-'.$item['id'], 'card');

            if ($illustration === null) {
                return $item;
            }

            return [
                ...$item,
                'image' => $illustration['src'],
                'image_alt' => $illustration['alt'],
                'image_srcset' => $illustration['srcSet'],
                'image_sizes' => $illustration['sizes'],
                'image_is_illustration' => true,
            ];
        }, $items);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(int $limit): array
    {
        return array_values(PublicEventResource::collection(
            Event::query()
                ->published()
                ->upcomingFromToday()
                // displayLocationName() reads the master location, and strict
                // mode turns a missing eager load into a 500 rather than an N+1.
                ->with(['category', 'location'])
                ->orderBy('start_at')
                ->orderBy('id')
                ->limit($limit)
                ->get()
        )->resolve());
    }

    /**
     * The newest published reflection, or null when there is none.
     *
     * PRD 7.1 block 8 defines a reflection as an article in the Renungan
     * category. The category is seeded, but a parish that has not written one
     * yet gets no section rather than a placeholder.
     *
     * @return array<string, mixed>|null
     */
    private function devotion(): ?array
    {
        $slug = (string) config('homepage.devotion_category_slug');

        $post = Post::query()
            ->published()
            ->whereHas('category', fn (Builder $query): Builder => $query->where('slug', $slug))
            ->with('category')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        return $post === null ? null : PublicPostResource::make($post)->resolve();
    }

    /**
     * Every active service, in the order the Pelayanan module defines.
     *
     * There is no homepage-specific service limit setting, so this does not
     * invent one. The module's own ordered scope decides the sequence.
     *
     * @return list<array<string, mixed>>
     */
    private function services(): array
    {
        $items = array_values(PublicServiceCardResource::collection(
            Service::query()
                ->active()
                ->ordered()
                ->with('thumbnailMedia.variants')
                ->get()
        )->resolve());

        return array_map(function (array $item): array {
            if ($item['thumbnail'] !== null) {
                return $item;
            }

            $illustration = HomepageIllustration::for('service-'.$item['id'], 'thumb');

            if ($illustration === null) {
                return $item;
            }

            return [
                ...$item,
                'thumbnail' => $illustration['src'],
                'thumbnail_alt' => $illustration['alt'],
                'thumbnail_srcset' => $illustration['srcSet'],
                'thumbnail_sizes' => $illustration['sizes'],
                'thumbnail_is_illustration' => true,
            ];
        }, $items);
    }

    /**
     * The newest ready photos from published albums.
     *
     * home_gallery_limit counts photos, not albums: that is what the setting's
     * label and PRD 7.1 block 10 describe. A photo whose media never finished
     * processing is excluded by scopeReady(), and a draft album by whereHas().
     *
     * @return list<array<string, mixed>>
     */
    private function gallery(int $limit): array
    {
        return array_values(PublicGalleryTeaserResource::collection(
            GalleryPhoto::query()
                ->ready()
                ->whereHas('album', fn (Builder $query): Builder => $query->published())
                ->with(['media.variants', 'album'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
        )->resolve());
    }

    /**
     * Labeled illustration tiles for an empty gallery.
     *
     * Only when the gallery has no real photos and the feature is on. These are
     * illustrations, not album photos, so they are a separate prop and the
     * interface renders them without album links.
     *
     * @param  list<array<string, mixed>>  $photos
     * @return list<array<string, mixed>>
     */
    private function galleryIllustrations(array $photos): array
    {
        if ($photos !== []) {
            return [];
        }

        return array_slice(HomepageIllustration::all('gallery'), 0, 6);
    }

    private function intSetting(string $key, int $default): int
    {
        $value = $this->settings->get($key, $default);

        return is_int($value) ? $value : (int) $value;
    }

    private function boolSetting(string $key): bool
    {
        return (bool) $this->settings->get($key);
    }

    private function stringSetting(string $key): ?string
    {
        $value = $this->settings->get($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
