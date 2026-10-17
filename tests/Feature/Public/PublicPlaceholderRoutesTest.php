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
| and that ten of them answer 200 for any slug matching [a-z0-9-]+. That was a
| deliberate, dated gap against PRD XC-E3, which requires a draft or unpublished
| record to return 404 to the public: nothing in a placeholder knows whether a
| record exists, so it cannot tell a real article from an invented one.
|
| Every detail placeholder has now been replaced by a module route with model
| binding, the last being Galeri in Phase 08A (D-65). What remains here is the
| 404 assertion for the four slug-bound modules and the route-table pin, so a
| placeholder that quietly reappears — or a route name that disappears — is
| still caught.
|
| The two file routes are here for the same reason. They are the only endpoints
| on the site that do not return a screen, and a placeholder that quietly starts
| returning HTML would break the one client that cares.
|
| @see docs/DECISIONS.md D-31, D-65
|
*/

/*
| All placeholder detail routes are gone.
|
| Berita and Agenda left in Phase 05, Pelayanan in Phase 06B, Komunitas in
| Phase 07, and Galeri in Phase 08A — each replaced by a route with model
| binding, which is what makes a missing slug 404 by itself (XC-E3). What
| replaced each one is asserted where the module lives rather than here; this
| file keeps the notice that there is nothing left to replace, and the route
| table assertion below still pins the route names.
|
| @see docs/DECISIONS.md D-31, D-65
*/

/*
| XC-E3, one module at a time.
|
| These four answer 404 for a slug nobody published. It is asserted where the
| module lives, and named here so a regression is visible in one place.
*/
it('answers 404 for a published-module slug rather than 200 for anything', function (string $url) {
    $this->get($url)->assertNotFound();
})->with([
    'berita' => ['/berita/slug-yang-tidak-pernah-dibuat'],
    'agenda' => ['/agenda/slug-yang-tidak-pernah-dibuat'],
    'komunitas' => ['/komunitas/slug-yang-tidak-pernah-dibuat'],
    'galeri' => ['/galeri/slug-yang-tidak-pernah-dibuat'],
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
