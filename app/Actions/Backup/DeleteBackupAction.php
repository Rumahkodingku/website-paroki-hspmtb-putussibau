<?php

namespace App\Actions\Backup;

use App\Exceptions\BackupException;
use App\Models\Backup;
use App\Services\AuditLogger;
use App\Services\Backup\BackupStorage;

/**
 * Deletes the file first, then the metadata.
 *
 * If the disk refuses to delete, the row stays and records the failure. A
 * deleted row with a surviving file would hide an orphan from the retention
 * and reconciliation tools.
 */
final class DeleteBackupAction
{
    public function __construct(
        private readonly BackupStorage $storage,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Backup $backup): void
    {
        if ($backup->restores()->active()->exists()) {
            throw BackupException::because('operation_in_progress');
        }

        if ($backup->path !== null && $this->storage->exists($backup->path)
            && ! $this->storage->delete($backup->path)) {
            $backup->forceFill([
                'failure_code' => 'delete_failed',
                'failure_message' => __('backups.errors.delete_failed'),
            ])->save();

            $this->audit->recordModel(
                'backups',
                'backup_delete_failed',
                'failure',
                $backup,
                [],
                [],
                ['backup_id' => $backup->uuid, 'error_code' => 'delete_failed'],
                $backup->type->label(),
            );

            throw BackupException::because('delete_failed');
        }

        $this->audit->recordModel(
            'backups',
            'backup_deleted',
            'success',
            $backup,
            [],
            [],
            ['backup_id' => $backup->uuid, 'type' => $backup->type->value],
            $backup->type->label(),
        );

        $backup->delete();
    }
}
