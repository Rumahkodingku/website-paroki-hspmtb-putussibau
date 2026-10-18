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
| The download route is now the Dokumen controller's (Phase 08B). The route-table
| pin below keeps its name, so a placeholder that quietly reappears — or a route
| name that disappears — is still caught.
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
        'dokumen.index',
        'dokumen.unduh',
        'kontak.index',
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
