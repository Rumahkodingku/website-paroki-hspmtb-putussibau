<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Community;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\PageSeo;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Http\Response;

/**
 * The public URL set, generated from the same scopes the public pages use.
 *
 * There is no crawling here. A wrong URL in a sitemap is worse than a missing
 * one, and the only routes this application wants indexed are the ones it can
 * name itself.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        return response()->view('seo.sitemap', [
            'urls' => $this->urls(),
        ])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @return list<array{loc: string, lastmod: string|null}>
     */
    private function urls(): array
    {
        $urls = [];

        /** @var array<string, array{route: string, label: string, title: string|null, description: string|null}> $pages */
        $pages = (array) config('seo.static_pages', []);

        $pageRows = PageSeo::query()
            ->whereIn('page_key', array_keys($pages))
            ->get()
            ->keyBy('page_key');

        foreach ($pages as $key => $page) {
            $this->add($urls, route($page['route']), $pageRows->get($key)?->updated_at->toAtomString());
        }

        foreach (Post::query()->published()->get(['slug', 'updated_at']) as $post) {
            $this->add($urls, route('berita.show', $post->slug), $post->updated_at->toAtomString());
        }

        foreach (Event::query()->published()->get(['slug', 'updated_at']) as $event) {
            $this->add($urls, route('agenda.show', $event->slug), $event->updated_at->toAtomString());
        }

        foreach (Service::query()->active()->get(['slug', 'updated_at']) as $service) {
            $this->add($urls, route('pelayanan.show', $service->slug), $service->updated_at->toAtomString());
        }

        foreach (Community::query()->published()->get(['slug', 'updated_at']) as $community) {
            $this->add($urls, route('komunitas.show', $community->slug), $community->updated_at->toAtomString());
        }

        foreach (Area::query()->active()->get(['slug', 'updated_at']) as $area) {
            $this->add($urls, route('area.show', $area->slug), $area->updated_at->toAtomString());
        }

        foreach (GalleryAlbum::query()->published()->get(['slug', 'updated_at']) as $gallery) {
            $this->add($urls, route('galeri.show', $gallery->slug), $gallery->updated_at->toAtomString());
        }

        return array_values($urls);
    }

    /**
     * @param  array<string, array{loc: string, lastmod: string|null}>  $urls
     */
    private function add(array &$urls, string $loc, ?string $lastmod): void
    {
        if (isset($urls[$loc])) {
            return;
        }

        $urls[$loc] = [
            'loc' => $loc,
            'lastmod' => $lastmod,
        ];
    }
}
