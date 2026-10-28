<?php

namespace App\Actions\Backup;

use App\Enums\BackupSource;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Exceptions\BackupException;
use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Backup\BackupHealth;
use Illuminate\Support\Str;

/**
 * Creates the record before any heavy work starts, then hands the work to the
 * queue. A request never waits for a dump.
 */
final class CreateBackupAction
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly BackupHealth $health,
    ) {}

    public function handle(BackupType $type, ?string $note, User $actor): Backup
    {
        if (! $this->health->binaryStatus()['ready']) {
            throw BackupException::because('binary_missing');
        }

        if (! $this->health->queueReady()) {
            throw BackupException::because('queue_unavailable');
        }

        if (BackupRestore::query()->active()->exists()) {
            throw BackupException::because('operation_in_progress');
        }

        $backup = Backup::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'source' => BackupSource::Manual,
            'status' => BackupStatus::Queued,
            'validation_status' => BackupValidationStatus::Pending,
            'disk' => (string) config('backups.disk'),
            'created_by' => $actor->getKey(),
            'created_by_label' => $actor->name,
            'note' => $note,
        ]);

        $this->audit->recordModel(
            'backups',
            'backup_started',
            'success',
            $backup,
            [],
            [],
            [
                'backup_id' => $backup->uuid,
                'type' => $backup->type->value,
                'source' => $backup->source->value,
            ],
            $backup->type->label(),
        );

        CreateBackupJob::dispatch($backup->getKey())
            ->onConnection(config('backups.queue_connection'))
            ->onQueue(config('backups.queue'));

        return $backup;
    }
}
