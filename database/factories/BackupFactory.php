<?php

namespace Database\Factories;

use App\Enums\BackupSource;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Models\Backup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Backup>
 */
class BackupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'type' => BackupType::Database,
            'source' => BackupSource::Manual,
            'status' => BackupStatus::Success,
            'validation_status' => BackupValidationStatus::Passed,
            'disk' => 'backups',
            'path' => 'archives/'.$uuid.'/hspmtb-db-2026-01-01-020000.sql.gz',
            'original_filename' => null,
            'file_size' => fake()->numberBetween(1024, 5_000_000),
            'checksum' => hash('sha256', $uuid),
            'format_version' => '1',
            'note' => null,
            'failure_code' => null,
            'failure_message' => null,
            'created_by' => null,
            'created_by_label' => 'Sistem',
            'started_at' => Carbon::now()->subMinute(),
            'finished_at' => Carbon::now(),
            'duration_ms' => 60000,
            'validated_at' => Carbon::now(),
            'file_missing_at' => null,
            'protected_until' => null,
            'scheduled_slot' => null,
        ];
    }

    public function full(): static
    {
        return $this->state(fn (): array => [
            'type' => BackupType::Full,
            'path' => 'archives/'.Str::uuid()->toString().'/hspmtb-full-2026-01-01-023000.zip',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => BackupStatus::Failed,
            'validation_status' => BackupValidationStatus::Failed,
            'path' => null,
            'checksum' => null,
            'failure_code' => 'dump_failed',
            'failure_message' => 'Backup gagal dibuat.',
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'source' => BackupSource::Scheduled,
            'scheduled_slot' => 'database:'.Carbon::now()->toDateString(),
        ]);
    }

    public function preRestore(): static
    {
        return $this->state(fn (): array => [
            'source' => BackupSource::PreRestore,
            'protected_until' => Carbon::now()->addDays(30),
        ]);
    }
}
