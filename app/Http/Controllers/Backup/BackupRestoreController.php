<?php

namespace App\Http\Controllers\Backup;

use App\Actions\Backup\PrepareRestoreAction;
use App\Actions\Backup\RestoreBackupAction;
use App\Exceptions\BackupException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backup\PrepareRestoreRequest;
use App\Http\Requests\Backup\RestoreBackupRequest;
use App\Models\Backup;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BackupRestoreController extends Controller
{
    public function prepare(
        PrepareRestoreRequest $request,
        Backup $backup,
        PrepareRestoreAction $action,
    ): RedirectResponse {
        try {
            $action->handle($backup, $request->user());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('backups.messages.restore_preparing')]);

            return to_route('backups.show', $backup);
        } catch (BackupException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->safeMessage()]);

            return back()->withErrors(['restore' => $exception->safeMessage()]);
        }
    }

    public function store(
        RestoreBackupRequest $request,
        Backup $backup,
        RestoreBackupAction $action,
    ): RedirectResponse {
        $restore = $request->restore();

        if ($restore === null) {
            return back()->withErrors(['restore_id' => __('backups.errors.operation_in_progress')]);
        }

        try {
            $action->handle($backup, $restore, $request->user());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('backups.messages.restore_started')]);

            return to_route('backups.show', $backup);
        } catch (BackupException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->safeMessage()]);

            return back()->withErrors(['restore' => $exception->safeMessage()]);
        }
    }
}
