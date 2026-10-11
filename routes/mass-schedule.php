<?php

use App\Http\Controllers\MassSchedule\LocationController;
use App\Http\Controllers\MassSchedule\MassScheduleController;
use App\Http\Controllers\MassSchedule\ScheduleNoticeController;
use App\Http\Controllers\MassSchedule\SpecialMassController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureAccountIsActive::class, EnsureAdminAccess::class])
    ->prefix('admin')
    ->group(function () {
        // Lokasi Misa
        Route::get('/jadwal-misa/lokasi', [LocationController::class, 'index'])->name('locations.index');
        Route::get('/jadwal-misa/lokasi/tambah', [LocationController::class, 'create'])->name('locations.create');
        Route::post('/jadwal-misa/lokasi', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/jadwal-misa/lokasi/{location}/ubah', [LocationController::class, 'edit'])->name('locations.edit');
        Route::patch('/jadwal-misa/lokasi/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/jadwal-misa/lokasi/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

        // Misa Rutin
        // The module summary used to live here. It is gone: four counts
        // with nowhere to go is not a destination, and the four lists are
        // what the module is for. The URL stays, though, and redirects to
        // the recurring-mass list, because it is what PRD 5.2 names and
        // because every breadcrumb in the module still points here.
        Route::get('/jadwal-misa', fn () => to_route('mass-schedules.list'))->name('mass-schedules.index');
        Route::get('/jadwal-misa/misa-rutin', [MassScheduleController::class, 'misaRutin'])->name('mass-schedules.list');
        Route::get('/jadwal-misa/misa-rutin/tambah', [MassScheduleController::class, 'create'])->name('mass-schedules.create');
        Route::post('/jadwal-misa/misa-rutin', [MassScheduleController::class, 'store'])->name('mass-schedules.store');
        Route::get('/jadwal-misa/misa-rutin/{mass_schedule}/ubah', [MassScheduleController::class, 'edit'])->name('mass-schedules.edit');
        Route::patch('/jadwal-misa/misa-rutin/{mass_schedule}', [MassScheduleController::class, 'update'])->name('mass-schedules.update');
        Route::delete('/jadwal-misa/misa-rutin/{mass_schedule}', [MassScheduleController::class, 'destroy'])->name('mass-schedules.destroy');

        Route::post('/jadwal-misa/misa-rutin/{mass_schedule}/duplikat', [MassScheduleController::class, 'duplicate'])
            ->name('mass-schedules.duplicate');

        // Misa Khusus
        Route::get('/jadwal-misa/misa-khusus', [SpecialMassController::class, 'index'])->name('special-masses.index');
        Route::get('/jadwal-misa/misa-khusus/tambah', [SpecialMassController::class, 'create'])->name('special-masses.create');
        Route::post('/jadwal-misa/misa-khusus', [SpecialMassController::class, 'store'])->name('special-masses.store');
        Route::get('/jadwal-misa/misa-khusus/{special_mass}/ubah', [SpecialMassController::class, 'edit'])->name('special-masses.edit');
        Route::patch('/jadwal-misa/misa-khusus/{special_mass}', [SpecialMassController::class, 'update'])->name('special-masses.update');
        Route::delete('/jadwal-misa/misa-khusus/{special_mass}', [SpecialMassController::class, 'destroy'])->name('special-masses.destroy');

        // Pemberitahuan Perubahan Jadwal
        Route::get('/jadwal-misa/pemberitahuan-jadwal', [ScheduleNoticeController::class, 'index'])->name('schedule-notices.index');
        Route::get('/jadwal-misa/pemberitahuan-jadwal/tambah', [ScheduleNoticeController::class, 'create'])->name('schedule-notices.create');
        Route::post('/jadwal-misa/pemberitahuan-jadwal', [ScheduleNoticeController::class, 'store'])->name('schedule-notices.store');
        Route::get('/jadwal-misa/pemberitahuan-jadwal/{schedule_notice}/ubah', [ScheduleNoticeController::class, 'edit'])->name('schedule-notices.edit');
        Route::patch('/jadwal-misa/pemberitahuan-jadwal/{schedule_notice}', [ScheduleNoticeController::class, 'update'])->name('schedule-notices.update');
        Route::delete('/jadwal-misa/pemberitahuan-jadwal/{schedule_notice}', [ScheduleNoticeController::class, 'destroy'])->name('schedule-notices.destroy');
    });
