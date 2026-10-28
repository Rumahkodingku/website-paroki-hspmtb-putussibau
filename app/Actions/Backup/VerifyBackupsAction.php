<?php

namespace App\Actions\Backup;

use App\Models\Backup;
use App\Services\AuditLogger;
use App\Services\Backup\BackupStorage;

final class VerifyBackupsAction
{
    public function __construct(
        private readonly BackupStorage $storage,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{missing:int, changed:int, restored:int}
     */
    public function handle(): array
    {
        $missing = 0;
        $changed = 0;
        $restored = 0;

        foreach (Backup::query()->whereNotNull('path')->get() as $backup) {
            $path = (string) $backup->path;

            if (! $this->storage->exists($path)) {
                if ($backup->file_missing_at === null) {
                    $backup->forceFill(['file_missing_at' => now()])->save();

                    $this->audit->system(
                        'backups',
                        'backup_validation_failed',
                        'failure',
                        __('audit.descriptions.backup_validation_failed'),
                        ['backup_id' => $backup->uuid, 'error_code' => 'file_missing'],
                        Backup::class,
                        (string) $backup->getKey(),
                        $backup->type->label(),
                    );

                    $missing++;
                }

                continue;
            }

            $size = $this->storage->size($path);

            if ($size !== null && $backup->file_size !== null && $size !== $backup->file_size) {
                $backup->forceFill([
                    'validation_status' => 'failed',
                    'failure_code' => 'file_changed',
                    'failure_message' => __('backups.errors.file_changed'),
                ])->save();

                $this->audit->system(
                    'backups',
                    'backup_validation_failed',
                    'failure',
                    __('audit.descriptions.backup_validation_failed'),
                    ['backup_id' => $backup->uuid, 'error_code' => 'file_changed'],
                    Backup::class,
                    (string) $backup->getKey(),
                    $backup->type->label(),
                );

                $changed++;

                continue;
            }

            if ($backup->file_missing_at !== null) {
                $backup->forceFill(['file_missing_at' => null])->save();
                $restored++;
            }
        }

        return ['missing' => $missing, 'changed' => $changed, 'restored' => $restored];
    }
}
