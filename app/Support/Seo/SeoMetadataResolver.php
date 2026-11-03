<?php

namespace App\Support\Seo;

use App\Models\Area;
use App\Models\Community;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\PageSeo;
use App\Models\Post;
use App\Models\Service;
use App\Services\HtmlSanitizer;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The page-specific metadata every public response carries.
 *
 * Blade renders it into the first HTML response, and React reads the same
 * values after hydration. One resolver means a crawler and a browser cannot
 * disagree about the title, the canonical URL, or whether a page is indexable.
 *
 * @see docs/plan-implementation/plan-seo.md
 */
final class SeoMetadataResolver
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly CanonicalUrlResolver $canonical,
    ) {}

    /**
     * @return array{
     *     title: string,
     *     description: string|null,
     *     canonical: string,
     *     ogImage: string|null,
     *     ogType: 'website'|'article',
     *     robots: string
     * }
     */
    public function resolve(Request $request): array
    {
        $route = $request->route();
        $name = $route instanceof Route ? $route->getName() : null;

        $metadata = match ($name) {
            'berita.show' => $this->postMetadata($request),
            'agenda.show' => $this->eventMetadata($request),
            'pelayanan.show' => $this->serviceMetadata($request),
            'komunitas.show' => $this->communityMetadata($request),
            'area.show' => $this->areaMetadata($request),
            'galeri.show' => $this->galleryMetadata($request),
            default => $this->staticMetadata($request, $name),
        };

        return [
            ...$metadata,
            'canonical' => $this->canonical->resolve($request),
            'robots' => $this->robots($request, $name),
        ];
    }

    /**
     * @return array{title: string, description: string|null, ogImage: string|null, ogType: 'website'|'article'}
     */
    private function postMetadata(Request $request): array
    {
        $post = $request->route('post');

        if (! $post instanceof Post) {
            return $this->fallbackMetadata();
        }

        return [
            'title' => $post->displayMetaTitle(),
            'description' => $post->displayDescription(),
            'ogImage' => $this->absolute($this->storageUrl($post->featured_image))
                ?? $this->globalOgImage(),
            'ogType' => 'article',
        ];
    }

    /**
     * @return array{title: string, description: string|null, ogImage: string|null, ogType: 'website'|'article'}
     */
    private function eventMetadata(Request $request): array
    {
        $event = $request->route('event');

        if (! $event instanceof Event) {
            return $this->fallbackMetadata();
        }

        return [
            'title' => $event->title,
            'description' => $event->plainDescription(),
            'ogImage' => $this->absolute($this->storageUrl($event->poster_path))
                ?? $this->globalOgImage(),
            'ogType' => 'article',
        ];
    }

    /**
     * @return array{title: string, description: string|null, ogImage: string|null, ogType: 'website'|'article'}
     */
    private function serviceMetadata(Request $request): array
    {
        $service = $request->route('service');

        if (! $service instanceof Service) {
            return $this->fallbackMetadata();
        }

        return [
            'title' => $service->title,
            'description' => $this->plain($service->summary)
                ?? $this->plain($service->introduction),
            'ogImage' => $this->absolute($service->thumbnailMedia?->url('medium'))
                ?? $this->globalOgImage(),
            'ogType' => 'website',
        ];
    }

    /**
     * @return array{title: string, description: string|null, ogImage: string|null, ogType: 'website'|'article'}
     */
    private function communityMetadata(Request $request): array
    {
        $community = $request->route('community');

        if (! $community instanceof Community) {
            return $this->fallbackMetadata();
        }

        return [
            'title' => $community->name,
            'description' => $this->plain($community->description),
            'ogImage' => $this->absolute($community->thumbnailMedia?->url('medium'))
                ?? $this->globalOgImage(),
            'ogType' => 'website',
        ];
    }

    /**
     * @return array{title: string, description: string|null, ogImage: string|null, ogType: 'website'|'article'}
     */
    private function areaMetadata(Request $request): array
    {
        $area = $request->route('area');

        if (! $area instanceof Area) {
            return $this->fallbackMetadata();
        }

        return [
            'title' => $area->name,
            'description' => $this->plain($area->description),
            'ogImage' => $this->absolute($area->photoMedia?->url('medium'))
                ?? $this->globalOgImage(),
            'ogType' => 'website',
        ];
    }

    /**
     * @return array{title: string, description: string|null, ogImage: string|null, ogType: 'website'|'article'}
     */
    private function galleryMetadata(Request $request): array
    {
        $gallery = $request->route('gallery');

        if (! $gallery instanceof GalleryAlbum) {
            return $this->fallbackMetadata();
        }

        return [
            'title' => $gallery->title,
            'description' => $this->plain($gallery->description),
            'ogImage' => $this->absolute($gallery->coverPhoto?->media?->url('medium'))
                ?? $this->globalOgImage(),
            'ogType' => 'website',
        ];
    }

    /**
     * A page in config/seo.php, with its optional page_seo row.
     *
     * @return array{title: string, description: string|null, ogImage: string|null, ogType: 'website'|'article'}
     */
    private function staticMetadata(Request $request, ?string $routeName): array
    {
        $page = $this->staticPage($routeName);

        if ($page === null) {
            return $this->fallbackMetadata();
        }

        $row = $this->pageRow($page['key']);
        $globalDescription = $this->setting('seo_default_description');

        $description = $page['key'] === 'home'
            ? ($row?->meta_description ?: ($globalDescription ?: $this->setting('short_description')))
            : ($row?->meta_description ?: ($page['description'] ?: $globalDescription));

        $title = $row?->meta_title
            ?: ($page['title'] ?: $this->setting('seo_default_title'));

        if ($page['key'] === 'home') {
            $title = $title
                ?: $this->setting('parish_short_name')
                ?: $this->setting('parish_name');
        }

        $ogImage = $this->absolute(
            $row instanceof PageSeo ? $row->media?->url('medium') : null,
        ) ?? $this->globalOgImage();

        if ($page['key'] === 'home') {
            $ogImage ??= $this->absolute((string) config('homepage.fallback_hero_og_image'));
        }

        return [
            'title' => $this->nonEmpty($title) ?? (string) config('app.name'),
            'description' => $this->nonEmpty($description),
            'ogImage' => $ogImage,
            'ogType' => 'website',
        ];
    }

    /**
     * @return array{key: string, route: string, label: string, title: string|null, description: string|null}|null
     */
    private function staticPage(?string $routeName): ?array
    {
        /** @var array<string, array{route: string, label: string, title: string|null, description: string|null}> $pages */
        $pages = (array) config('seo.static_pages', []);

        foreach ($pages as $key => $page) {
            if ($page['route'] === $routeName) {
                return ['key' => $key, ...$page];
            }
        }

        return null;
    }

    private function pageRow(string $key): ?PageSeo
    {
        return PageSeo::query()
            ->with(['media.variants'])
            ->where('page_key', $key)
            ->first();
    }

    /**
     * @return array{title: string, description: string|null, ogImage: string|null, ogType: 'website'|'article'}
     */
    private function fallbackMetadata(): array
    {
        return [
            'title' => $this->setting('seo_default_title')
                ?? $this->setting('parish_name')
                ?? (string) config('app.name'),
            'description' => $this->setting('seo_default_description'),
            'ogImage' => $this->globalOgImage(),
            'ogType' => 'website',
        ];
    }

    private function robots(Request $request, ?string $routeName): string
    {
        $search = $request->query('q');
        $hasSearch = is_string($search) && trim($search) !== '';

        if (
            $request->is('admin', 'admin/*', 'install', 'install/*')
            || $routeName === 'posts.preview'
            || $hasSearch
        ) {
            return 'noindex, follow';
        }

        return 'index, follow';
    }

    private function setting(string $key): ?string
    {
        $value = $this->settings->get($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function nonEmpty(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }

    private function plain(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $text = app(HtmlSanitizer::class)->toPlainText($html);

        return $text === '' ? null : Str::limit($text, 200);
    }

    private function storageUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return Storage::disk((string) config('media.variant_disk'))->url($path);
    }

    private function absolute(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $url) === 1 || str_starts_with($url, '//')) {
            return $url;
        }

        $base = rtrim((string) config('app.url'), '/');

        if ($base === '') {
            $base = rtrim(url('/'), '/');
        }

        return $base.'/'.ltrim($url, '/');
    }

    private function globalOgImage(): ?string
    {
        return $this->absolute($this->setting('seo_default_og_image'));
    }
}
