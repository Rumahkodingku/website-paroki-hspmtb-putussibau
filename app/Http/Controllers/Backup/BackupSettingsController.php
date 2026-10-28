<?php

namespace App\Http\Controllers\Backup;

use App\Actions\Backup\UpdateBackupSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backup\UpdateBackupSettingsRequest;
use App\Http\Resources\Backup\BackupSettingsResource;
use App\Models\Backup;
use App\Services\Backup\BackupHealth;
use App\Services\Backup\BackupSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BackupSettingsController extends Controller
{
    public function edit(
        BackupSettingsService $settings,
        BackupHealth $health,
    ): Response {
        Gate::authorize('settings', Backup::class);

        return Inertia::render('admin/backup/pengaturan', [
            'settingsPage' => BackupSettingsResource::make([
                'settings' => $settings->values(),
                'timezones' => array_values((array) config('backups.timezones', [])),
                'readiness' => $health->readiness(),
            ])->resolve(),
        ]);
    }

    public function update(
        UpdateBackupSettingsRequest $request,
        UpdateBackupSettingsAction $action,
    ): RedirectResponse {
        $action->handle($request->settingsPayload(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('backups.messages.settings_updated')]);

        return to_route('backups.settings.edit');
    }
}
