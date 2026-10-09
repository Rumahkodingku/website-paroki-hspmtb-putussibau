<?php

use App\Services\SiteSettingsService;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
|
| Phase 03 gave the public site its routes. Roadmap section 17 lists what the
| public suite has to cover; this file is the backend half of it.
|
| What is asserted here is the contract, not the content. Every page is a
| placeholder by design - roadmap section 10 permits placeholder public routes
| for navigation testing and forbids everything else - so there is no
| proposition, no taxonomy and no rendering of a record to check. What there is
| to check is that the route exists, reaches the page it claims to, needs no
| session, and does not shadow a path it should leave alone.
|
| The absence of props is asserted as carefully as their presence. A placeholder
| page that starts receiving props before its module exists is how "no CRUD, no
| domain query" quietly stops being true, and nothing else in the suite would
| notice.
|
| @see docs/DECISIONS.md D-31
|
*/

/**
 * Every public page, as [url, expected Inertia component].
 *
 * Written out rather than derived from the route table on purpose: a test that
 * reads the routes it is checking cannot tell a correct route from a wrong one,
 * because both sides move together. This list is the assertion.
 */
dataset('public pages', fn (): array => [
    '/' => ['/', 'public/beranda'],
    '/profil' => ['/profil', 'public/profil'],
    '/profil/sejarah' => ['/profil/sejarah', 'public/profil/sejarah'],
    '/profil/visi-misi' => ['/profil/visi-misi', 'public/profil/visi-misi'],
    '/profil/wilayah' => ['/profil/wilayah', 'public/profil/wilayah'],
    '/profil/pastor' => ['/profil/pastor', 'public/profil/pastor'],
    '/profil/struktur' => ['/profil/struktur', 'public/profil/struktur'],
    '/jadwal-misa' => ['/jadwal-misa', 'public/jadwal-misa'],
    '/berita' => ['/berita', 'public/berita'],
    '/agenda' => ['/agenda', 'public/agenda'],
    '/pelayanan' => ['/pelayanan', 'public/pelayanan'],
    '/komunitas' => ['/komunitas', 'public/komunitas'],
    '/galeri' => ['/galeri', 'public/galeri'],
    '/kontak' => ['/kontak', 'public/kontak'],
    '/download' => ['/download', 'public/download'],
    '/design-system' => ['/design-system', 'public/design-system'],
]);

it('renders the expected page for every public route', function (string $url, string $component) {
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with('public pages');

it('lets a guest reach every public route without signing in', function (string $url) {
    // assertOk() is the whole point of this test. A redirect to /admin/login
    // would mean the public shell had picked up one of the three admin
    // middleware layers by accident, which is invisible until somebody clicks a
    // link from the footer and lands on a login form.
    $response = $this->get($url);

    $response->assertOk();
    expect($response->getStatusCode())->toBeLessThan(300);
})->with('public pages');

/**
 * The placeholder contract from roadmap section 10, restated as a test.
 *
 * A page that receives props is a page that has started depending on something,
 * and at this stage there is nothing legitimate for it to depend on - no
 * module, no model, no service. The moment one of these fails, the page has
 * grown business logic, which is the exact thing this phase forbids.
 */
it('sends no page-specific props from a placeholder', function (string $url) {
    $props = $this->get($url)->viewData('page')['props'];

    $shared = ['name', 'auth', 'locale', 'displayTimezone', 'sidebarOpen', 'seo', 'errors'];

    expect(array_diff(array_keys($props), $shared))->toBe([]);
})->with('public pages');

it('serves the same shared SEO defaults on every public page', function (string $url) {
    // NFR-SEO: the meta tags must be in the first HTML response, and the blade
    // template renders them from site_settings rather than from props. This
    // checks the props half of that contract is present everywhere, so a page
    // cannot reach the Seo component without the data to fill it.
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('seo.appUrl')
            ->has('seo.siteName')
            ->has('seo.title')
            ->has('seo.description')
            ->has('seo.ogImage')
        );
})->with('public pages');

/**
 * NFR-SEO, and the whole reason the blade template duplicates what Seo renders.
 *
 * SSR is off (D-26), so a crawler and a WhatsApp unfurl read the response body
 * and nothing else. If the Open Graph tags were React-only, every link preview of
 * the parish would be blank and no component test would notice.
 *
 * The settings are written first because the blade guards those tags on them:
 * og:title and og:description are conditional on seo_default_title and
 * seo_default_description, and nothing is seeded (D-12). On an unconfigured site
 * they are legitimately absent, which is itself worth knowing but is not what
 * this test is checking. What it checks is that once the parish fills them in,
 * the tags land in the *initial HTML*, server-side.
 */
it('renders the Open Graph and Twitter tags in the initial HTML, not only after hydration', function () {
    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Paroki HSPMTB Putussibau',
        'seo_default_title' => 'Paroki HSPMTB Putussibau',
        'seo_default_description' => '[ISI: deskripsi default untuk pratinjau tautan]',
    ]);

    $html = $this->get('/berita')->assertOk()->getContent();

    expect($html)
        ->toContain('property="og:title"')
        ->toContain('property="og:description"')
        ->toContain('property="og:url"')
        ->toContain('property="og:site_name"')
        ->toContain('property="og:type"')
        ->toContain('property="og:locale"')
        ->toContain('name="twitter:card"')
        ->toContain('name="twitter:description"')
        ->toContain('rel="canonical"')
        ->toContain('name="description"')
        ->toContain('name="robots"');

    /*
     * Exactly one of each. Two og:title tags means the client layer is no longer
     * replacing the server layer because their head-keys drifted apart, which is
     * invisible in a browser and is what this count is here to catch. The keys
     * live in two files - resources/views/app.blade.php as data-inertia and
     * resources/js/components/seo.tsx as head-key - and nothing but a test makes
     * them stay in step.
     */
    foreach (['og:title', 'og:description', 'og:url', 'og:locale'] as $property) {
        expect(substr_count($html, 'property="'.$property.'"'))->toBe(1);
    }

    foreach (['description', 'robots', 'twitter:card'] as $name) {
        expect(substr_count($html, 'name="'.$name.'"'))->toBe(1);
    }
});

/**
 * The complement of the test above: an unconfigured setting produces no tag
 * rather than an empty one.
 *
 * Crawlers and link unfurlers both read an empty description as a description
 * and render a blank preview, so "no value" and "empty value" have to stay
 * different all the way to the response body.
 */
it('omits an Open Graph tag rather than emitting it empty', function () {
    app(SiteSettingsService::class)->set('parish_name', 'Paroki HSPMTB Putussibau');

    $html = $this->get('/berita')->assertOk()->getContent();

    expect($html)
        ->not->toContain('name="description"')
        ->not->toContain('property="og:description"')
        // og:type and robots carry no setting, so they are always present.
        ->toContain('property="og:type"')
        ->toContain('name="robots"');
});

it('keeps an unknown public path at 404', function () {
    $this->get('/halaman-yang-tidak-ada')->assertNotFound();
});

/**
 * The one shape the placeholder routes deliberately do not accept.
 *
 * PRD NFR-SEO asks for clean slug URLs, so an uppercase slug or one containing
 * spaces is not a slug, and answering for it would mean the wildcard claims paths
 * it has no business claiming - including a future /berita/feed.json.
 */
it('does not answer for a path that is not a slug', function (string $path) {
    $this->get($path)->assertNotFound();
})->with([
    'uppercase' => ['/berita/Berita-Penting'],
    'spaces' => ['/berita/berita%20penting'],
    'trailing extension' => ['/berita/feed.json'],
]);

it('keeps the admin area protected while the public shell grows', function () {
    // Roadmap section 17: "Public shell changes do not break authentication."
    // The admin routes did not change in this phase, so this is a guard rather
    // than a new behaviour - but it is the one that would catch a middleware or
    // group edit made while adding public routes.
    $this->get('/admin')->assertRedirect(route('login'));
});

it('does not register any public route inside the admin prefix', function () {
    // A mistyped group would put a public page behind three middleware layers or,
    // worse, put a public page in front of one. Both are invisible in
    // route:list's default order and cheap to assert.
    $strays = collect(app('router')->getRoutes()->getRoutes())
        ->reject(fn ($route): bool => str_starts_with((string) $route->uri(), 'admin'))
        ->reject(fn ($route): bool => $route->getName() === null)
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'admin'))
        ->map(fn ($route): string => (string) $route->uri())
        ->values()
        ->all();

    expect($strays)->toBeEmpty();
});
