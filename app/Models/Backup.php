<?php

namespace App\Models;

use App\Enums\BackupSource;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use Database\Factories\BackupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One backup artifact and its lifecycle.
 *
 * The file lives on a private disk. Nothing here exposes an absolute path to
 * the browser; `path` is relative to `disk` and only the download controller
 * resolves it.
 *
 * @property int $id
 * @property string $uuid
 * @property BackupType $type
 * @property BackupSource $source
 * @property BackupStatus $status
 * @property BackupValidationStatus $validation_status
 * @property string $disk
 * @property string|null $path
 * @property string|null $original_filename
 * @property int|null $file_size
 * @property string|null $checksum
 * @property string|null $format_version
 * @property string|null $note
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property int|null $created_by
 * @property string|null $created_by_label
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int|null $duration_ms
 * @property Carbon|null $validated_at
 * @property Carbon|null $file_missing_at
 * @property Carbon|null $protected_until
 * @property string|null $scheduled_slot
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'uuid',
    'type',
    'source',
    'status',
    'validation_status',
    'disk',
    'path',
    'original_filename',
    'file_size',
    'checksum',
    'format_version',
    'note',
    'failure_code',
    'failure_message',
    'created_by',
    'created_by_label',
    'started_at',
    'finished_at',
    'duration_ms',
    'validated_at',
    'file_missing_at',
    'protected_until',
    'scheduled_slot',
])]
class Backup extends Model
{
    /** @use HasFactory<BackupFactory> */
    use HasFactory;

    /**
     * @return HasMany<BackupRestore, $this>
     */
    public function restores(): HasMany
    {
        return $this->hasMany(BackupRestore::class);
    }

    /**
     * @return HasMany<BackupRestore, $this>
     */
    public function safetyFor(): HasMany
    {
        return $this->hasMany(BackupRestore::class, 'safety_backup_id');
    }

    /**
     * @param  Builder<Backup>  $query
     * @return Builder<Backup>
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * @param  Builder<Backup>  $query
     * @return Builder<Backup>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('status', BackupStatus::Success)
            ->where('validation_status', BackupValidationStatus::Passed)
            ->whereNotNull('path')
            ->whereNull('file_missing_at');
    }

    public function isAvailable(): bool
    {
        return $this->status === BackupStatus::Success
            && $this->validation_status === BackupValidationStatus::Passed
            && $this->path !== null
            && $this->file_missing_at === null;
    }

    public function isDownloadable(): bool
    {
        return $this->isAvailable();
    }

    public function isRestorable(): bool
    {
        return $this->isAvailable();
    }

    public function isRunning(): bool
    {
        return $this->status->isActive();
    }

    public function isProtected(): bool
    {
        return $this->protected_until !== null && $this->protected_until->isFuture();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BackupType::class,
            'source' => BackupSource::class,
            'status' => BackupStatus::class,
            'validation_status' => BackupValidationStatus::class,
            'file_size' => 'integer',
            'created_by' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'validated_at' => 'immutable_datetime',
            'file_missing_at' => 'immutable_datetime',
            'protected_until' => 'immutable_datetime',
        ];
    }
}
