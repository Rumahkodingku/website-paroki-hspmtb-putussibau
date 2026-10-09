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
 * @return array<string, array{0: string, 1: string, 2: string}>
 */
function placeholderDetailRoutes(): array
{
    return [
        'berita' => ['/berita/contoh-artikel', 'public/berita/show', 'Fase 4'],
        'agenda' => ['/agenda/contoh-agenda', 'public/agenda/show', 'Fase 4'],
        'pelayanan' => ['/pelayanan/contoh-pelayanan', 'public/pelayanan/show', 'Fase 5'],
        'komunitas' => ['/komunitas/contoh-komunitas', 'public/komunitas/show', 'Fase 5'],
        'galeri' => ['/galeri/contoh-album', 'public/galeri/show', 'Fase 6'],
    ];
}

dataset('placeholder detail routes', fn (): array => placeholderDetailRoutes());

it('still answers for a detail route, so navigation never lands on a 404', function (string $url, string $component) {
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with('placeholder detail routes');

/**
 * The gap, stated as a test rather than as prose.
 *
 * This asserts the placeholder behaviour rather than the behaviour the PRD wants,
 * because it is the honest description of what is true today. When the Berita
 * phase replaces this route, this test fails - and the fix is to delete it in the
 * same change, which is the whole point of writing it down.
 */
it('answers 200 for any well-formed slug, which is the gap D-31 records', function () {
    $response = $this->get('/berita/slug-yang-tidak-pernah-dibuat');

    $response->assertOk();

    // And it is honest about being empty: the slug is echoed into the page, so a
    // reviewer can see this is a shell rather than a published article.
    expect($response->viewData('page')['component'])
        ->toBe('public/berita/show');
});

it('passes the slug through to the page as a prop', function () {
    $this->get('/berita/slug-yang-dipakai')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/berita/show')
            ->where('slug', 'slug-yang-dipakai')
        );
});

it('rejects anything that is not a clean slug', function (string $path) {
    $this->get($path)->assertNotFound();
})->with([
    'uppercase letters' => ['/berita/Berita-Penting'],
    'encoded space' => ['/berita/berita%20penting'],
    'dot in the path' => ['/berita/feed.json'],
    'slash' => ['/berita/a/b'],
]);

describe('the .ics endpoint', function () {
    it('responds with a calendar content type', function () {
        $response = $this->get('/agenda/contoh-agenda/ics');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    });

    /**
     * A syntactically valid VCALENDAR with no event in it. That combination is
     * the point: a calendar app opens it, finds nothing, and says so. What this
     * route must never do is emit a VEVENT, because inventing a start and an end
     * in WIB is Agenda business logic and a fabricated date in a parishioner's
     * calendar is exactly the kind of invented parish data AGENTS.md forbids.
     */
    it('returns a valid calendar that contains no fabricated event', function () {
        $body = (string) $this->get('/agenda/contoh-agenda/ics')
            ->assertOk()
            ->getContent();

        expect($body)
            ->toStartWith('BEGIN:VCALENDAR')
            ->toContain('VERSION:2.0')
            ->toContain('CALSCALE:GREGORIAN')
            ->toContain('END:VCALENDAR')
            ->not->toContain('BEGIN:VEVENT')
            ->not->toContain('BEGIN:VTIMEZONE')
            ->toContain('[ISI:');
    });

    /**
     * RFC 5545 requires CRLF. A calendar written with bare LF is rejected by
     * several clients without saying why, which is the kind of bug that only
     * shows up once a parishioner has already downloaded the file.
     */
    it('uses CRLF line endings', function () {
        $body = (string) $this->get('/agenda/contoh-agenda/ics')
            ->assertOk()
            ->getContent();

        expect($body)->toContain("\r\n");
    });

    it('does not accept a slug that is not one', function () {
        $this->get('/agenda/Bukan-Slug/ics')->assertNotFound();
    });
});

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
        'profil.visiMisi',
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
