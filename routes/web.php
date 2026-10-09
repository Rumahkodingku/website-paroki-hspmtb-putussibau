<?php

use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminAccess;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Phase 03 builds the shell, not the content.
|
| Every route below exists so the public navigation has somewhere real to
| point. That is the whole reason they exist: Phase 01 rendered the nine non-home
| menu entries as disabled spans precisely because a link to a 404 is worse than
| no link, and that was the correct call while the routes were missing. They are
| no longer missing, so the disabled branch is gone and the menu is real.
|
| None of them query anything. No model, no resource, no controller, no props.
| Placeholder copy is written in the React page, which is the only place a
| sentence like "[ISI: halaman ini diimplementasikan pada Fase Profil]" can
| honestly live - a PHP or Blade string would ship placeholder text as if it
| were content.
|
| There are two shapes here and the difference matters:
|
|   - a page route, rendered with Route::inertia(), and
|   - a file route, which returns a download rather than a screen.
|
| The second kind exists because PRD 5.1 lists /agenda/{slug}/ics and
| /download/{id}/unduh among the public URLs. They are part of the information
| architecture a visitor can reach, so omitting them would leave the phase
| incomplete. What they must not do is pretend to work: a .ics carrying
| fabricated dates in WIB is Agenda business logic, and a download endpoint with
| a counter is Download business logic. Both therefore return an honest
| placeholder in the correct response shape, and D-31 records the hand-off to
| the phase that owns each one.
|
| See docs/DECISIONS.md D-31.
|
*/

Route::inertia('/', 'public/beranda')->name('home');

Route::inertia('/profil', 'public/profil')->name('profil.index');
Route::inertia('/profil/sejarah', 'public/profil/sejarah')->name('profil.sejarah');
Route::inertia('/profil/visi-misi', 'public/profil/visi-misi')->name('profil.visiMisi');
Route::inertia('/profil/wilayah', 'public/profil/wilayah')->name('profil.wilayah');
Route::inertia('/profil/pastor', 'public/profil/pastor')->name('profil.pastor');
Route::inertia('/profil/struktur', 'public/profil/struktur')->name('profil.struktur');

Route::inertia('/jadwal-misa', 'public/jadwal-misa')->name('jadwal-misa.index');

Route::inertia('/berita', 'public/berita')->name('berita.index');
Route::get('/berita/{slug}', fn (string $slug) => Inertia::render('public/berita/show', ['slug' => $slug]))
    ->where('slug', '[a-z0-9-]+')
    ->name('berita.show');

Route::inertia('/agenda', 'public/agenda')->name('agenda.index');
Route::get('/agenda/{slug}', fn (string $slug) => Inertia::render('public/agenda/show', ['slug' => $slug]))
    ->where('slug', '[a-z0-9-]+')
    ->name('agenda.show');

Route::inertia('/pelayanan', 'public/pelayanan')->name('pelayanan.index');
Route::get('/pelayanan/{slug}', fn (string $slug) => Inertia::render('public/pelayanan/show', ['slug' => $slug]))
    ->where('slug', '[a-z0-9-]+')
    ->name('pelayanan.show');

Route::inertia('/komunitas', 'public/komunitas')->name('komunitas.index');
Route::get('/komunitas/{slug}', fn (string $slug) => Inertia::render('public/komunitas/show', ['slug' => $slug]))
    ->where('slug', '[a-z0-9-]+')
    ->name('komunitas.show');

Route::inertia('/galeri', 'public/galeri')->name('galeri.index');
Route::get('/galeri/{slug}', fn (string $slug) => Inertia::render('public/galeri/show', ['slug' => $slug]))
    ->where('slug', '[a-z0-9-]+')
    ->name('galeri.show');

Route::inertia('/kontak', 'public/kontak')->name('kontak.index');

Route::get('/agenda/{slug}/ics', function (string $slug): HttpResponse {
    return Response::make(implode("\r\n", [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Paroki HSPMTB Putussibau//Agenda//ID',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-PARISH-PLACEHOLDER:[ISI: agenda belum tersedia]',
        'END:VCALENDAR',
        '',
    ]), 200, ['Content-Type' => 'text/calendar; charset=utf-8']);
})->where('slug', '[a-z0-9-]+')->name('agenda.ics');

Route::get('/download/{id}/unduh', fn (): HttpResponse => Response::make(
    "[ISI: berkas belum tersedia. Modul Download dibangun pada Fase 4.]\n",
    200,
    [
        'Content-Type' => 'application/octet-stream',
        'Content-Disposition' => 'attachment; filename="belum-tersedia.txt"',
    ],
))->where('id', '[0-9]+')->name('download.unduh');

Route::inertia('/download', 'public/download')->name('download.index');

/*
 * The design-system showcase. Not in PRD 5.1, and D-31 says so: it is a
 * temporary reference page proving the shell works end to end, removed once the
 * public site is stable. The page sends noindex,follow, which keeps it out of
 * search results in the meantime. The route itself is reachable in every
 * environment on purpose - an environment-gated route could not be covered by
 * the Pest or Playwright suites, and an untested page is exactly what roadmap
 * section 17 exists to prevent.
 */
Route::inertia('/design-system', 'public/design-system')->name('design-system.index');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Everything below /admin is the CMS. PRD AUTH-R1 requires three things on
| every one of these routes: an authenticated session, an active account
| (D-08), and authorization. The order matters - EnsureAccountIsActive must
| run before EnsureAdminAccess so a deactivated Super Admin is told their
| account was switched off rather than being shown a permission error.
|
| See docs/DECISIONS.md D-19.
|
*/

Route::middleware(['auth', 'verified', EnsureAccountIsActive::class, EnsureAdminAccess::class])
    ->prefix('admin')
    ->group(function () {
        Route::inertia('/', 'admin/dashboard')->name('dashboard');

        Route::get('/pengaturan', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingsController::class, 'update'])->name('settings.update');

        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    });

require __DIR__.'/settings.php';
