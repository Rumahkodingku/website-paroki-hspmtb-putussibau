<?php

namespace App\Actions\Backup;

use App\Enums\BackupSource;
use App\Enums\BackupStatus;
use App\Enums\BackupValidationStatus;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Services\Backup\BackupCreator;
use App\Services\Backup\BackupSettingsService;
use Illuminate\Support\Str;

/**
 * Creates the backup that makes a restore recoverable.
 *
 * It is a normal backup with source `pre_restore`, created and validated by the
 * same creator as every other archive. Only after it succeeds does the caller
 * mark the restore ready.
 */
final class CreateSafetyBackupAction
{
    public function __construct(
        private readonly BackupCreator $creator,
        private readonly BackupSettingsService $settings,
    ) {}

    public function handle(Backup $target, BackupRestore $restore): Backup
    {
        $safety = Backup::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => $target->type,
            'source' => BackupSource::PreRestore,
            'status' => BackupStatus::Queued,
            'validation_status' => BackupValidationStatus::Pending,
            'disk' => (string) config('backups.disk'),
            'created_by_label' => 'Safety backup',
            'note' => 'Safety backup sebelum pemulihan '.$target->uuid,
        ]);

        $this->creator->create($safety);

        $protectedUntil = now()->addDays((int) $this->settings->values()['retention_safety_days']);

        $safety->forceFill(['protected_until' => $protectedUntil])->save();
        $restore->forceFill(['safety_backup_id' => $safety->getKey()])->save();

        return $safety;
    }
}
