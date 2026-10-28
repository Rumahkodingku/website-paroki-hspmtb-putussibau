<?php

namespace App\Models;

use Database\Factories\BackupSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The single row of backup schedule and retention settings.
 *
 * The service owns reading and writing; this model stays thin.
 *
 * @property int $id
 * @property bool $schedule_enabled
 * @property bool $database_enabled
 * @property bool $full_enabled
 * @property string $database_time
 * @property int $full_day
 * @property string $full_time
 * @property string $timezone
 * @property int $retention_daily_days
 * @property int $retention_weekly_weeks
 * @property int $retention_invalid_upload_days
 * @property int $retention_safety_days
 * @property int $max_upload_megabytes
 * @property int $max_expanded_megabytes
 * @property int $max_files
 * @property int $max_compression_ratio
 * @property int $low_disk_threshold_megabytes
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'schedule_enabled',
    'database_enabled',
    'full_enabled',
    'database_time',
    'full_day',
    'full_time',
    'timezone',
    'retention_daily_days',
    'retention_weekly_weeks',
    'retention_invalid_upload_days',
    'retention_safety_days',
    'max_upload_megabytes',
    'max_expanded_megabytes',
    'max_files',
    'max_compression_ratio',
    'low_disk_threshold_megabytes',
    'updated_by',
])]
class BackupSetting extends Model
{
    /** @use HasFactory<BackupSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schedule_enabled' => 'boolean',
            'database_enabled' => 'boolean',
            'full_enabled' => 'boolean',
            'full_day' => 'integer',
            'retention_daily_days' => 'integer',
            'retention_weekly_weeks' => 'integer',
            'retention_invalid_upload_days' => 'integer',
            'retention_safety_days' => 'integer',
            'max_upload_megabytes' => 'integer',
            'max_expanded_megabytes' => 'integer',
            'max_files' => 'integer',
            'max_compression_ratio' => 'integer',
            'low_disk_threshold_megabytes' => 'integer',
            'updated_by' => 'integer',
        ];
    }
}
