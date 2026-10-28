<?php

namespace App\Actions\Backup;

use App\Enums\BackupRestoreStatus;
use App\Exceptions\BackupException;
use App\Jobs\PrepareRestoreJob;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Str;

/**
 * Starts the non-destructive half of a restore: the safety backup.
 *
 * The destructive half is a separate POST after the operator has seen the
 * safety backup succeed.
 */
final class PrepareRestoreAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Backup $backup, User $actor): BackupRestore
    {
        if (! $backup->isRestorable()) {
            throw BackupException::because('file_missing');
        }

        if (BackupRestore::query()->active()->exists()) {
            throw BackupException::because('operation_in_progress');
        }

        $restore = BackupRestore::query()->create([
            'uuid' => (string) Str::uuid(),
            'backup_id' => $backup->getKey(),
            'status' => BackupRestoreStatus::Queued,
            'requested_by' => $actor->getKey(),
            'requested_by_label' => $actor->name,
        ]);

        $this->audit->recordModel(
            'backups',
            'restore_requested',
            'success',
            $backup,
            [],
            [],
            [
                'backup_id' => $backup->uuid,
                'operation_id' => $restore->uuid,
                'type' => $backup->type->value,
                'requested_by' => $actor->getKey(),
            ],
            $backup->type->label(),
        );

        PrepareRestoreJob::dispatch($restore->getKey())
            ->onConnection(config('backups.queue_connection'))
            ->onQueue(config('backups.queue'));

        return $restore;
    }
}
