<?php

namespace App\Jobs;

use App\Enums\BackupStatus;
use App\Enums\BackupValidationStatus;
use App\Models\Backup;
use App\Services\AuditLogger;
use App\Services\Backup\BackupCreator;
use App\Services\Backup\BackupHealth;
use App\Services\Backup\BackupStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Runs one backup away from the HTTP request.
 *
 * A cache lock keeps a manual and a scheduled backup from writing at the same
 * time. The creator updates the row and audits both success and failure, so this
 * job only owns the lock and the retry policy.
 */
class CreateBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $backupId) {}

    public function handle(
        BackupCreator $creator,
        BackupHealth $health,
        BackupStorage $storage,
        AuditLogger $audit,
    ): void {
        $health->markWorkerHeartbeat();

        $backup = Backup::query()->find($this->backupId);

        if ($backup === null) {
            return;
        }

        $lock = Cache::lock('backups:exclusive', (int) config('backups.timeouts.lock_seconds', 7200));

        if (! $lock->block(300)) {
            $backup->forceFill([
                'status' => BackupStatus::Failed,
                'validation_status' => BackupValidationStatus::Failed,
                'failure_code' => 'operation_in_progress',
                'failure_message' => __('backups.errors.operation_in_progress'),
                'finished_at' => now(),
            ])->save();

            $audit->system(
                'backups',
                'backup_failed',
                'failure',
                __('audit.descriptions.backup_failed'),
                [
                    'backup_id' => $backup->uuid,
                    'type' => $backup->type->value,
                    'error_code' => 'operation_in_progress',
                ],
                Backup::class,
                (string) $backup->getKey(),
                $backup->type->label(),
            );

            return;
        }

        try {
            $creator->create($backup);
        } finally {
            $lock->release();
            $storage->cleanup($backup->uuid);
        }
    }
}
