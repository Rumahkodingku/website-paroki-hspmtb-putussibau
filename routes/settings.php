<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminAccess;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Account Routes (/admin/akun)
|--------------------------------------------------------------------------
|
| PRD 5.2 puts profile, password and Super Admin account management under
| /admin/akun. Everything here sits under the same three middleware layers as
| the rest of /admin/* - authenticated, active account, authorized - because
| these are admin pages, not public settings.
|
| See docs/DECISIONS.md D-19.
|
*/

Route::middleware(['auth', 'verified', EnsureAccountIsActive::class, EnsureAdminAccess::class])
    ->prefix('admin/akun')
    ->group(function () {
        Route::redirect('/', 'profile');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('security', [SecurityController::class, 'edit'])
            ->middleware(RequirePassword::class)
            ->name('security.edit');

        Route::put('password', [SecurityController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('user-password.update');

        Route::inertia('appearance', 'admin/akun/appearance')->name('appearance.edit');
    });
