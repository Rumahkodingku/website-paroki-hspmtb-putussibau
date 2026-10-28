<?php

namespace App\Actions\Backup;

use App\Enums\BackupRestoreStatus;
use App\Exceptions\BackupException;
use App\Jobs\RestoreBackupJob;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * The destructive half: only accepts a ready operation whose safety backup
 * already exists.
 */
final class RestoreBackupAction
{
    public function handle(Backup $backup, BackupRestore $restore, User $actor): void
    {
        if ($restore->backup_id !== $backup->getKey()) {
            throw BackupException::because('operation_in_progress');
        }

        if (! $restore->isReadyForConfirmation()) {
            throw BackupException::because(
                $restore->status === BackupRestoreStatus::Ready
                    ? 'ready_expired'
                    : 'operation_in_progress'
            );
        }

        if (! $backup->isRestorable() || $backup->path === null) {
            throw BackupException::because('file_missing');
        }

        if (! Storage::disk($backup->disk)->exists($backup->path)) {
            $backup->forceFill(['file_missing_at' => now()])->save();

            throw BackupException::because('file_missing');
        }

        $restore->forceFill([
            'status' => BackupRestoreStatus::Queued,
            'confirmed_at' => now(),
            'requested_by' => $actor->getKey(),
            'requested_by_label' => $actor->name,
        ])->save();

        RestoreBackupJob::dispatch($restore->getKey())
            ->onConnection(config('backups.queue_connection'))
            ->onQueue(config('backups.queue'));
    }
}
