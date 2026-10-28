<?php

namespace App\Actions\Backup;

use App\Enums\BackupRestoreStatus;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Services\AuditLogger;
use App\Services\Backup\BackupRetention;
use App\Services\Backup\BackupStorage;

final class PruneBackupsAction
{
    public function __construct(
        private readonly BackupRetention $retention,
        private readonly BackupStorage $storage,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{expired:int, deleted:int, failed:int}
     */
    public function handle(): array
    {
        $expired = $this->expireReadyRestores();
        $deleted = 0;
        $failed = 0;

        foreach ($this->retention->queryEligible()->get() as $backup) {
            if ($backup->path !== null && $this->storage->exists($backup->path)
                && ! $this->storage->delete($backup->path)) {
                $backup->forceFill([
                    'failure_code' => 'delete_failed',
                    'failure_message' => __('backups.errors.delete_failed'),
                ])->save();

                $this->audit->system(
                    'backups',
                    'backup_delete_failed',
                    'failure',
                    __('audit.descriptions.backup_delete_failed'),
                    ['backup_id' => $backup->uuid, 'error_code' => 'delete_failed'],
                    Backup::class,
                    (string) $backup->getKey(),
                    $backup->type->label(),
                );

                $failed++;

                continue;
            }

            $this->audit->system(
                'backups',
                'backup_retention_deleted',
                'success',
                __('audit.descriptions.backup_retention_deleted'),
                [
                    'backup_id' => $backup->uuid,
                    'type' => $backup->type->value,
                    'source' => $backup->source->value,
                ],
                Backup::class,
                (string) $backup->getKey(),
                $backup->type->label(),
            );

            $backup->delete();
            $deleted++;
        }

        return ['expired' => $expired, 'deleted' => $deleted, 'failed' => $failed];
    }

    private function expireReadyRestores(): int
    {
        $expired = 0;

        $restores = BackupRestore::query()
            ->where('status', BackupRestoreStatus::Ready)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($restores as $restore) {
            $restore->forceFill([
                'status' => BackupRestoreStatus::Expired,
                'finished_at' => now(),
            ])->save();

            $this->audit->system(
                'backups',
                'restore_expired',
                'success',
                __('audit.descriptions.restore_expired'),
                ['operation_id' => $restore->uuid, 'backup_id' => $restore->backup_id],
                Backup::class,
                (string) $restore->backup_id,
            );

            $expired++;
        }

        return $expired;
    }
}
