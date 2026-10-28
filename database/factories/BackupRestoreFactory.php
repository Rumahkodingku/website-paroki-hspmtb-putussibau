<?php

namespace Database\Factories;

use App\Enums\BackupComponentResult;
use App\Enums\BackupRestoreStatus;
use App\Models\Backup;
use App\Models\BackupRestore;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<BackupRestore>
 */
class BackupRestoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'backup_id' => Backup::factory(),
            'safety_backup_id' => null,
            'status' => BackupRestoreStatus::Ready,
            'database_result' => null,
            'files_result' => null,
            'requested_by' => null,
            'requested_by_label' => 'Super Admin',
            'confirmed_at' => null,
            'expires_at' => Carbon::now()->addHour(),
            'started_at' => null,
            'finished_at' => null,
            'duration_ms' => null,
            'failure_code' => null,
            'failure_message' => null,
            'verification' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn (): array => [
            'status' => BackupRestoreStatus::Running,
            'started_at' => Carbon::now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => BackupRestoreStatus::Success,
            'database_result' => BackupComponentResult::Success,
            'files_result' => BackupComponentResult::Skipped,
            'started_at' => Carbon::now()->subMinute(),
            'finished_at' => Carbon::now(),
            'duration_ms' => 60000,
        ]);
    }
}
