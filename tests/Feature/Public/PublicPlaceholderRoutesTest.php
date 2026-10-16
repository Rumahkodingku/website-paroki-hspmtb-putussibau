<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Placeholder public routes
|--------------------------------------------------------------------------
|
| This file exists to make a temporary decision impossible to forget.
|
| D-31 records that Phase 03 built every PRD 5.1 public route as a placeholder,
| and that ten of them answer 200 for any slug matching [a-z0-9-]+. That is a
| deliberate, dated gap against PRD XC-E3, which requires a draft or unpublished
| record to return 404 to the public: nothing in a placeholder knows whether a
| record exists, so it cannot tell a real article from an invented one.
|
| A gap that lives only in a paragraph of a decisions document is a gap that
| survives. The routes below are listed here with the phase that must replace
| each of them, and the first test fails if one disappears without being
| replaced. Deleting a placeholder route is the correct move; deleting it
| silently is not.
|
| The two file routes are here for the same reason. They are the only endpoints
| on the site that do not return a screen, and a placeholder that quietly starts
| returning HTML would break the one client that cares.
|
| @see docs/DECISIONS.md D-31
|
*/

/**
 * Detail routes that answer 200 for any slug, and the phase that owns each.
 *
 * When a module lands, its entry here becomes a route with model binding, at
 * which point a missing slug 404s by itself and XC-E3 is satisfied for that
 * module. Removing the row is part of that work, not after it.
 *
 * Berita and Agenda left this list in Phase 05, together with the three tests
 * that asserted the placeholder behaviour and the four that covered the .ics
 * stub. What replaced them is not here but in the modules that own it:
 * tests/Feature/News/PublicPostPageTest.php,
 * tests/Feature/Agenda/PublicEventPageTest.php and
 * tests/Feature/Agenda/IcsTest.php.
 *
 * @return array<string, array{0: string, 1: string, 2: string}>
 */
function placeholderDetailRoutes(): array
{
    return [
        // Pelayanan left this list in Phase 06B, replaced by a route with model
        // binding. Its 404 behaviour is asserted in
        // tests/Feature/Pelayanan/PublicServicePageTest.php. See D-54.
        //
        // Komunitas left this list in Phase 07, replaced by a route with model
        // binding. Its 404 behaviour is asserted in
        // tests/Feature/Komunitas/PublicCommunityDetailTest.php. See D-55.
        'galeri' => ['/galeri/contoh-album', 'public/galeri/show', 'Fase 8'],
    ];
}

dataset('placeholder detail routes', fn (): array => placeholderDetailRoutes());

it('still answers for a detail route, so navigation never lands on a 404', function (string $url, string $component) {
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with('placeholder detail routes');

/*
| XC-E3, one module at a time.
|
| Berita and Agenda answer 404 for a slug nobody published. That is the whole of
| XC-E3 for those two URLs, and it is asserted where the module lives rather
| than here — this file exists to track what has NOT been done yet, and a
| statement that something has been done belongs with the thing that did it.
*/
it('answers 404 for a published-module slug rather than 200 for anything', function (string $url) {
    $this->get($url)->assertNotFound();
})->with([
    'berita' => ['/berita/slug-yang-tidak-pernah-dibuat'],
    'agenda' => ['/agenda/slug-yang-tidak-pernah-dibuat'],
    'komunitas' => ['/komunitas/slug-yang-tidak-pernah-dibuat'],
]);

it('rejects anything that is not a clean slug', function (string $path) {
    $this->get($path)->assertNotFound();
})->with([
    'uppercase letters' => ['/berita/Berita-Penting'],
    'encoded space' => ['/berita/berita%20penting'],
    'dot in the path' => ['/berita/feed.json'],
    'slash' => ['/berita/a/b'],
]);

describe('the download endpoint', function () {
    it('responds as an attachment rather than a page', function () {
        $response = $this->get('/download/1/unduh');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Disposition', 'attachment; filename="belum-tersedia.txt"');
    });

    /**
     * No counter, no filename from the database, no path from storage. The
     * Download phase owns all three, and a counter incrementing against a
     * placeholder would put a number in the admin that no download ever earned.
     */
    it('returns a placeholder body and nothing resembling a real file', function () {
        $body = (string) $this->get('/download/1/unduh')
            ->assertOk()
            ->getContent();

        expect($body)
            ->toContain('[ISI: berkas belum tersedia')
            ->not->toContain('%PDF')
            ->not->toContain('storage');
    });

    it('requires a numeric id', function () {
        $this->get('/download/abc/unduh')->assertNotFound();
    });
});

it('does not collide with the section index of the same name', function () {
    // /berita is a section and /berita/{slug} is a detail. If the constraint on
    // the detail ever widened, the index would become unreachable under a single
    // segment - the visitor would land on a placeholder detail instead of the
    // list, with no way back.
    $this->get('/berita')->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/berita'));
});

it('names every placeholder route in the route table', function () {
    // The pin itself. A placeholder route that quietly disappears leaves a link
    // in the navbar or footer pointing at a 404, which is the exact failure
    // Phase 03 was built to remove.
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route): ?string => $route->getName())
        ->filter();

    $expected = [
        'profil.index',
        'profil.sejarah',
        'profil.wilayah',
        'profil.pastor',
        'profil.struktur',
        'jadwal-misa.index',
        'berita.index',
        'berita.show',
        'agenda.index',
        'agenda.show',
        'agenda.ics',
        'pelayanan.index',
        'pelayanan.show',
        'komunitas.index',
        'komunitas.show',
        'galeri.index',
        'galeri.show',
        'kontak.index',
        'download.index',
        'download.unduh',
        'design-system.index',
    ];

    foreach ($expected as $name) {
        expect($names->contains($name))->toBeTrue(
            "Route [{$name}] is missing. Either it was removed without being ".
            'replaced by its module, or the list above needs updating. See '.
            'docs/DECISIONS.md D-31.'
        );
    }
});
