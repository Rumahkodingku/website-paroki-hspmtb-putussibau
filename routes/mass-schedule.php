<?php

use App\Http\Controllers\MassSchedule\LocationController;
use App\Http\Controllers\MassSchedule\MassScheduleController;
use App\Http\Controllers\MassSchedule\ScheduleNoticeController;
use App\Http\Controllers\MassSchedule\SpecialMassController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mass Schedule Routes (/admin)
|--------------------------------------------------------------------------
|
| PRD 5.2 names four URLs for this module — /admin/lokasi, /admin/jadwal-misa,
| /admin/misa-khusus and /admin/pemberitahuan-jadwal — and all four exist here
| under those exact paths. No redirect and no invented prefix, so an admin who
| has written the URL down from the specification finds the page.
|
| They sit in their own file, and their own middleware group, for the same
| reason routes/settings.php has one: the group is four middleware deep and the
| CMS is going to keep growing, and a file per module keeps web.php readable.
| The middleware list itself is not duplicated logic — it is the same four
| entries in the same order as everywhere else under /admin, and
| AdminAccessTest asserts every one of these routes carries all three layers.
| The order matters: EnsureAccountIsActive before EnsureAdminAccess, so an admin
| whose account was switched off is told that rather than shown a permission
| error (D-19, D-20).
|
| There is no destroy route anywhere in this module. Locations are deactivated
| rather than deleted (PRD 7.3), a schedule that is switched off stays in the
| table (MASS-04), and a special mass that has passed stays too (MASS-02). A
| DELETE endpoint here would be the only way to lose that history.
|
| @see docs/DECISIONS.md D-34, D-38
*/

Route::middleware(['auth', 'verified', EnsureAccountIsActive::class, EnsureAdminAccess::class])
    ->prefix('admin')
    ->group(function () {
        /*
         * Lokasi. The literal 'tambah' is declared before the {location} route
         * so it can never be captured as an id — the pattern is the same all the
         * way down, and it is why a location id of "tambah" cannot 404 here.
         */
        Route::get('/lokasi', [LocationController::class, 'index'])->name('locations.index');
        Route::get('/lokasi/tambah', [LocationController::class, 'create'])->name('locations.create');
        Route::post('/lokasi', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/lokasi/{location}/ubah', [LocationController::class, 'edit'])->name('locations.edit');
        Route::patch('/lokasi/{location}', [LocationController::class, 'update'])->name('locations.update');

        /*
         * Jadwal misa rutin. Also the module summary — the four counts and the
         * four shortcut cards live above this table rather than on a URL of
         * their own (D-38).
         */
        Route::get('/jadwal-misa', [MassScheduleController::class, 'index'])->name('mass-schedules.index');
        Route::get('/jadwal-misa/tambah', [MassScheduleController::class, 'create'])->name('mass-schedules.create');
        Route::post('/jadwal-misa', [MassScheduleController::class, 'store'])->name('mass-schedules.store');
        Route::get('/jadwal-misa/{mass_schedule}/ubah', [MassScheduleController::class, 'edit'])->name('mass-schedules.edit');
        Route::patch('/jadwal-misa/{mass_schedule}', [MassScheduleController::class, 'update'])->name('mass-schedules.update');

        /*
         * Duplication. A POST to a named sub-path rather than a query parameter
         * on update, because it creates a second record rather than changing
         * one, and it carries its own request class because the payload is
         * different: a destination location, not a full schedule.
         */
        Route::post('/jadwal-misa/{mass_schedule}/duplikat', [MassScheduleController::class, 'duplicate'])
            ->name('mass-schedules.duplicate');

        /*
         * Misa khusus.
         */
        Route::get('/misa-khusus', [SpecialMassController::class, 'index'])->name('special-masses.index');
        Route::get('/misa-khusus/tambah', [SpecialMassController::class, 'create'])->name('special-masses.create');
        Route::post('/misa-khusus', [SpecialMassController::class, 'store'])->name('special-masses.store');
        Route::get('/misa-khusus/{special_mass}/ubah', [SpecialMassController::class, 'edit'])->name('special-masses.edit');
        Route::patch('/misa-khusus/{special_mass}', [SpecialMassController::class, 'update'])->name('special-masses.update');

        /*
         * Pemberitahuan perubahan jadwal.
         */
        Route::get('/pemberitahuan-jadwal', [ScheduleNoticeController::class, 'index'])->name('schedule-notices.index');
        Route::get('/pemberitahuan-jadwal/tambah', [ScheduleNoticeController::class, 'create'])->name('schedule-notices.create');
        Route::post('/pemberitahuan-jadwal', [ScheduleNoticeController::class, 'store'])->name('schedule-notices.store');
        Route::get('/pemberitahuan-jadwal/{schedule_notice}/ubah', [ScheduleNoticeController::class, 'edit'])->name('schedule-notices.edit');
        Route::patch('/pemberitahuan-jadwal/{schedule_notice}', [ScheduleNoticeController::class, 'update'])->name('schedule-notices.update');
    });
