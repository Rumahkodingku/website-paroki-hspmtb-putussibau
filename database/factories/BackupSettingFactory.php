<?php

namespace Database\Factories;

use App\Models\BackupSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BackupSetting>
 */
class BackupSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 1,
            'schedule_enabled' => true,
            'database_enabled' => true,
            'full_enabled' => true,
            'database_time' => '02:00',
            'full_day' => 0,
            'full_time' => '02:30',
            'timezone' => 'Asia/Pontianak',
            'retention_daily_days' => 14,
            'retention_weekly_weeks' => 4,
            'retention_invalid_upload_days' => 7,
            'retention_safety_days' => 30,
            'max_upload_megabytes' => 256,
            'max_expanded_megabytes' => 4096,
            'max_files' => 10000,
            'max_compression_ratio' => 100,
            'low_disk_threshold_megabytes' => 1024,
            'updated_by' => null,
        ];
    }
}
