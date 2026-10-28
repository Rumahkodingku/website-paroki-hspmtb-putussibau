<?php

namespace App\Jobs;

use App\Actions\Backup\CreateSafetyBackupAction;
use App\Enums\BackupRestoreStatus;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Services\AuditLogger;
use App\Services\Backup\BackupHealth;
use App\Services\Backup\BackupSafeError;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Builds and validates the safety backup before the final confirmation.
 */
class PrepareRestoreJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 7200;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $restoreId) {}

    public function handle(
        CreateSafetyBackupAction $safetyAction,
        BackupHealth $health,
        BackupSafeError $errors,
        AuditLogger $audit,
    ): void {
        $health->markWorkerHeartbeat();

        $restore = BackupRestore::query()->find($this->restoreId);

        if ($restore === null) {
            return;
        }

        $backup = Backup::query()->find($restore->backup_id);

        if ($backup === null) {
            $this->fail($restore, __('backups.errors.file_missing'), $audit, 'file_missing');

            return;
        }

        $lock = Cache::lock('backups:exclusive', (int) config('backups.timeouts.lock_seconds', 7200));

        if (! $lock->block(300)) {
            $this->fail($restore, __('backups.errors.operation_in_progress'), $audit, 'operation_in_progress');

            return;
        }

        try {
            $restore->forceFill([
                'status' => BackupRestoreStatus::Preparing,
                'started_at' => now(),
                'failure_code' => null,
                'failure_message' => null,
            ])->save();

            $safety = $safetyAction->handle($backup, $restore);

            $restore->forceFill([
                'status' => BackupRestoreStatus::Ready,
                'safety_backup_id' => $safety->getKey(),
                'expires_at' => now()->addHour(),
                'confirmed_at' => null,
            ])->save();

            $metadata = [
                'backup_id' => $backup->uuid,
                'operation_id' => $restore->uuid,
                'safety_backup_id' => $safety->uuid,
                'type' => $backup->type->value,
                'requested_by' => $restore->requested_by,
            ];

            $audit->system(
                'backups',
                'restore_safety_created',
                'success',
                __('audit.descriptions.restore_safety_created'),
                $metadata,
                Backup::class,
                (string) $backup->getKey(),
                $backup->type->label(),
            );

            $audit->system(
                'backups',
                'restore_ready',
                'success',
                __('audit.descriptions.restore_ready'),
                $metadata,
                Backup::class,
                (string) $backup->getKey(),
                $backup->type->label(),
            );
        } catch (Throwable $exception) {
            $this->fail(
                $restore,
                $errors->message($exception),
                $audit,
                $errors->code($exception),
            );
        } finally {
            $lock->release();
        }
    }

    private function fail(BackupRestore $restore, string $message, AuditLogger $audit, string $code): void
    {
        $restore->forceFill([
            'status' => BackupRestoreStatus::Failed,
            'failure_code' => $code,
            'failure_message' => $message,
            'finished_at' => now(),
        ])->save();

        $audit->system(
            'backups',
            'restore_failed',
            'failure',
            __('audit.descriptions.restore_failed'),
            [
                'operation_id' => $restore->uuid,
                'backup_id' => $restore->backup_id,
                'error_code' => $code,
            ],
            Backup::class,
            (string) $restore->backup_id,
        );
    }
}
