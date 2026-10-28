<?php

namespace App\Http\Controllers\Backup;

use App\Actions\Backup\UploadBackupAction;
use App\Exceptions\BackupException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backup\UploadBackupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;

class BackupUploadController extends Controller
{
    public function store(
        UploadBackupRequest $request,
        UploadBackupAction $action,
    ): RedirectResponse {
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            return back()->withErrors(['file' => 'Pilih arsip backup terlebih dahulu.']);
        }

        try {
            $backup = $action->handle($file, $request->user());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('backups.messages.uploaded')]);

            return to_route('backups.show', $backup);
        } catch (BackupException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->safeMessage()]);

            return back()->withErrors(['file' => $exception->safeMessage()]);
        }
    }
}
