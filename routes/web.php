<?php

use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| The public site is built in later phases. Right now only a placeholder
| landing page exists so that the guest entry point is reachable.
|
*/

Route::inertia('/', 'welcome')->name('home');

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
        // Path is relative to the 'admin' prefix, so this is /admin.
        Route::inertia('/', 'admin/dashboard')->name('dashboard');

        // PRD 5.2: site settings and contact details live here, not under
        // /admin/akun, which stays per-account. See docs/DECISIONS.md D-23.
        Route::get('/pengaturan', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingsController::class, 'update'])->name('settings.update');

        // Media endpoints, no page. The pipeline is a foundation in Phase 01 and
        // the interface arrives with the first module that needs it.
        // See docs/DECISIONS.md D-24.
        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    });

require __DIR__.'/settings.php';
