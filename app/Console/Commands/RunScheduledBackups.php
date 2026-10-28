<?php

namespace App\Console\Commands;

use App\Enums\BackupSource;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use App\Services\AuditLogger;
use App\Services\Backup\BackupHealth;
use App\Services\Backup\BackupSafeError;
use App\Services\Backup\BackupSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns a configured slot into one backup.
 *
 * The unique `scheduled_slot` makes the command idempotent: a scheduler that
 * fires twice in the same minute still produces one record. When the worker is
 * not detected, it skips instead of filling the queue with jobs nobody runs.
 */
class RunScheduledBackups extends Command
{
    protected $signature = 'backups:run-scheduled';

    protected $description = 'Buat backup sesuai jadwal yang dikonfigurasi';

    public function __construct(
        private readonly BackupSettingsService $settings,
        private readonly BackupHealth $health,
        private readonly BackupSafeError $errors,
        private readonly AuditLogger $audit,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $values = $this->settings->values();

        if (! $values['schedule_enabled']) {
            return self::SUCCESS;
        }

        $now = now($this->settings->timezone());
        $created = 0;

        if ($values['database_enabled'] && $now->format('H:i') === $values['database_time']) {
            $created += $this->dispatchSlot(
                BackupType::Database,
                'database:'.$now->format('Y-m-d'),
            );
        }

        if ($values['full_enabled']
            && (int) $now->dayOfWeek === (int) $values['full_day']
            && $now->format('H:i') === $values['full_time']) {
            $created += $this->dispatchSlot(
                BackupType::Full,
                'full:'.$now->format('o-\WW'),
            );
        }

        if ($created > 0) {
            $this->components->info("{$created} backup terjadwal dimasukkan ke antrean.");
        }

        return self::SUCCESS;
    }

    private function dispatchSlot(BackupType $type, string $slot): int
    {
        if (Backup::query()->where('scheduled_slot', $slot)->exists()) {
            return 0;
        }

        if (! $this->health->queueReady()) {
            Log::warning('Scheduled backup skipped: the backup worker is not detected.', [
                'slot' => $slot,
            ]);

            return 0;
        }

        $backup = Backup::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'source' => BackupSource::Scheduled,
            'status' => BackupStatus::Queued,
            'validation_status' => BackupValidationStatus::Pending,
            'disk' => (string) config('backups.disk'),
            'created_by_label' => 'Penjadwal',
            'note' => 'Backup terjadwal',
            'scheduled_slot' => $slot,
        ]);

        try {
            $this->audit->system(
                'backups',
                'backup_started',
                'success',
                __('audit.descriptions.backup_started'),
                [
                    'backup_id' => $backup->uuid,
                    'type' => $type->value,
                    'source' => BackupSource::Scheduled->value,
                ],
                Backup::class,
                (string) $backup->getKey(),
                $type->label(),
            );

            CreateBackupJob::dispatch($backup->getKey())
                ->onConnection(config('backups.queue_connection'))
                ->onQueue(config('backups.queue'));

            return 1;
        } catch (Throwable $exception) {
            $code = $this->errors->code($exception);

            $backup->forceFill([
                'status' => BackupStatus::Failed,
                'validation_status' => BackupValidationStatus::Failed,
                'failure_code' => $code,
                'failure_message' => $this->errors->message($exception),
                'finished_at' => now(),
            ])->save();

            $this->audit->system(
                'backups',
                'backup_failed',
                'failure',
                __('audit.descriptions.backup_failed'),
                ['backup_id' => $backup->uuid, 'error_code' => $code],
                Backup::class,
                (string) $backup->getKey(),
                $type->label(),
            );

            Log::error('Scheduled backup dispatch failed.', [
                'backup_id' => $backup->uuid,
                'code' => $code,
            ]);

            return 0;
        }
    }
}
