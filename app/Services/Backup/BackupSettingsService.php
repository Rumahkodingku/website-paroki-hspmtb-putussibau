<?php

namespace App\Services\Backup;

use App\Models\BackupSetting;
use App\Models\User;

/**
 * The single access point for backup schedule, retention and limits.
 *
 * Defaults come from this class, never from a row that may not exist yet. The
 * settings row is only written when an admin saves the form.
 */
final class BackupSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'schedule_enabled' => true,
            'database_enabled' => true,
            'full_enabled' => true,
            'database_time' => '02:00',
            'full_day' => 0,
            'full_time' => '02:30',
            'timezone' => (string) config('app.display_timezone', 'Asia/Pontianak'),
            'retention_daily_days' => 14,
            'retention_weekly_weeks' => 4,
            'retention_invalid_upload_days' => 7,
            'retention_safety_days' => 30,
            'max_upload_megabytes' => 256,
            'max_expanded_megabytes' => 4096,
            'max_files' => 10000,
            'max_compression_ratio' => 100,
            'low_disk_threshold_megabytes' => 1024,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        $row = BackupSetting::query()->find(1);

        if ($row === null) {
            return $this->defaults();
        }

        return array_merge($this->defaults(), $row->only(array_keys($this->defaults())));
    }

    public function model(): BackupSetting
    {
        $settings = BackupSetting::query()->find(1);

        if ($settings !== null) {
            return $settings;
        }

        $settings = new BackupSetting;
        $settings->id = 1;
        $settings->fill($this->defaults());
        $settings->save();

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values, ?User $actor = null): BackupSetting
    {
        $settings = $this->model();
        $settings->fill(array_intersect_key($values, $this->defaults()));
        $settings->updated_by = $actor?->getKey();
        $settings->save();

        return $settings->refresh();
    }

    public function scheduleEnabled(): bool
    {
        return (bool) $this->values()['schedule_enabled'];
    }

    public function databaseEnabled(): bool
    {
        return (bool) $this->values()['database_enabled'];
    }

    public function fullEnabled(): bool
    {
        return (bool) $this->values()['full_enabled'];
    }

    public function timezone(): string
    {
        $timezone = (string) $this->values()['timezone'];

        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : (string) config('app.display_timezone', 'Asia/Pontianak');
    }

    public function uploadLimitBytes(): int
    {
        $configured = (int) config('backups.limits.max_upload_megabytes', 256);
        $value = (int) $this->values()['max_upload_megabytes'];

        return max(1, min($configured, $value)) * 1024 * 1024;
    }

    public function maxExpandedBytes(): int
    {
        $configured = (int) config('backups.limits.max_expanded_megabytes', 4096);
        $value = (int) $this->values()['max_expanded_megabytes'];

        return max(1, min($configured, $value)) * 1024 * 1024;
    }

    public function maxFiles(): int
    {
        $configured = (int) config('backups.limits.max_files', 10000);
        $value = (int) $this->values()['max_files'];

        return max(1, min($configured, $value));
    }

    public function maxCompressionRatio(): int
    {
        $configured = (int) config('backups.limits.max_compression_ratio', 100);
        $value = (int) $this->values()['max_compression_ratio'];

        return max(1, min($configured, $value));
    }
}
