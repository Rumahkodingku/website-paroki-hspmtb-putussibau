<?php

namespace App\Models;

use App\Enums\BackupComponentResult;
use App\Enums\BackupRestoreStatus;
use Database\Factories\BackupRestoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One restore attempt, kept separate from the backup it restores.
 *
 * A failed restore must never make the source backup look invalid, which is why
 * this is its own row rather than a status on `backups`.
 *
 * @property int $id
 * @property string $uuid
 * @property int $backup_id
 * @property int|null $safety_backup_id
 * @property BackupRestoreStatus $status
 * @property BackupComponentResult|null $database_result
 * @property BackupComponentResult|null $files_result
 * @property int|null $requested_by
 * @property string|null $requested_by_label
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int|null $duration_ms
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property array<string, mixed>|null $verification
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'uuid',
    'backup_id',
    'safety_backup_id',
    'status',
    'database_result',
    'files_result',
    'requested_by',
    'requested_by_label',
    'confirmed_at',
    'expires_at',
    'started_at',
    'finished_at',
    'duration_ms',
    'failure_code',
    'failure_message',
    'verification',
])]
class BackupRestore extends Model
{
    /** @use HasFactory<BackupRestoreFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Backup, $this>
     */
    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    /**
     * @return BelongsTo<Backup, $this>
     */
    public function safetyBackup(): BelongsTo
    {
        return $this->belongsTo(Backup::class, 'safety_backup_id');
    }

    /**
     * @param  Builder<BackupRestore>  $query
     * @return Builder<BackupRestore>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereIn('status', [
                BackupRestoreStatus::Queued->value,
                BackupRestoreStatus::Preparing->value,
                BackupRestoreStatus::Running->value,
            ])->orWhere(function (Builder $query): void {
                $query->where('status', BackupRestoreStatus::Ready->value)
                    ->where(function (Builder $query): void {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    });
            });
        });
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isReadyForConfirmation(): bool
    {
        return $this->status === BackupRestoreStatus::Ready
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BackupRestoreStatus::class,
            'database_result' => BackupComponentResult::class,
            'files_result' => BackupComponentResult::class,
            'requested_by' => 'integer',
            'duration_ms' => 'integer',
            'confirmed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'verification' => 'array',
        ];
    }
}
