<?php

/*
|--------------------------------------------------------------------------
| The URLs Master Data used to have
|--------------------------------------------------------------------------
|
| Every URL in here was live before D-48 and is still reachable, which is the
| whole point of the file: a bookmarked link is the difference between "the menu
| moved" and "the parish secretary's link is broken".
|
| There is a second reason these have a test at all. The redirect that keeps
| them alive is an unnamed-route hazard, and the failure it causes is one nobody
| would predict: `Route::redirect()` with no controller gives Wayfinder nowhere
| to file it, it lands in the generated module of the named route it redirects
| *from*, and the build dies on "Identifier 'index' has already been declared".
| They are named `legacy.*` for that reason, and this file is what would notice
| if somebody un-named them again and the build stopped failing for a different
| reason.
|
| @see docs/DECISIONS.md D-48
*/

use Illuminate\Support\Facades\Route;

test('every legacy URL redirects to where it now lives', function (string $from, string $to) {
    $this->get($from)->assertRedirect($to);
})->with([
    'lokasi' => ['/admin/jadwal-misa/lokasi', '/admin/master-data/lokasi'],
    'lokasi tambah' => ['/admin/jadwal-misa/lokasi/tambah', '/admin/master-data/lokasi/tambah'],
    'lokasi ubah' => ['/admin/jadwal-misa/lokasi/1/ubah', '/admin/master-data/lokasi/1/ubah'],

    'kategori berita' => ['/admin/kategori-berita', '/admin/master-data/kategori-berita'],
    'kategori berita tambah' => ['/admin/kategori-berita/tambah', '/admin/master-data/kategori-berita/tambah'],
    'kategori berita ubah' => ['/admin/kategori-berita/1/ubah', '/admin/master-data/kategori-berita/1/ubah'],

    'kategori agenda' => ['/admin/kategori-agenda', '/admin/master-data/kategori-agenda'],
    'kategori agenda tambah' => ['/admin/kategori-agenda/tambah', '/admin/master-data/kategori-agenda/tambah'],
    'kategori agenda ubah' => ['/admin/kategori-agenda/1/ubah', '/admin/master-data/kategori-agenda/1/ubah'],
]);

test('the redirect lands on a page that exists', function () {
    // A redirect to a 404 is a worse outcome than no redirect: it looks like it
    // worked. Signed in, so the request gets past the middleware and the answer
    // is about the target route rather than about the session.
    $admin = superAdmin();

    $this->actingAs($admin)
        ->get('/admin/jadwal-misa/lokasi')
        ->assertRedirect('/admin/master-data/lokasi');

    $this->actingAs($admin)->get('/admin/master-data/lokasi')->assertOk();
});

test('a legacy URL is not a second route to the same page', function () {
    // The old paths must redirect rather than serve. If a controller route came
    // back at /admin/kategori-berita, the redirect above would never fire and
    // two URLs would answer one page — which is the situation D-44 already
    // refuses to create for a slug.
    $this->get('/admin/kategori-berita')->assertRedirect('/admin/master-data/kategori-berita');
    $this->get('/admin/kategori-agenda')->assertRedirect('/admin/master-data/kategori-agenda');
    $this->get('/admin/jadwal-misa/lokasi')->assertRedirect('/admin/master-data/lokasi');
});

test('no route name is declared twice', function () {
    // How the duplicate actually happened the first time: the old category
    // routes were left in the Berita and Agenda route files when the new ones
    // were added to the Master Data one — all of them one file now, which does
    // not make the mistake less possible. Laravel does not complain
    // about that — the last registration simply wins — so the only place it
    // shows up is a generated module with two exports of the same name and a
    // build that refuses to finish.
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->all();

    $duplicates = array_keys(array_filter(array_count_values($names), fn ($n) => $n > 1));

    expect($duplicates)->toBe([]);
});

test('the three lookup tables live under one prefix', function () {
    // The reason for the move, stated so it cannot quietly reverse: one feature,
    // one prefix, one group in the sidebar.
    foreach ([
        'locations.index',
        'post-categories.index',
        'event-categories.index',
    ] as $name) {
        // route() hands back an absolute URL, so the assertion is on the path
        // rather than on the whole string.
        expect(parse_url(route($name), PHP_URL_PATH))->toStartWith('/admin/master-data/');
    }
});
