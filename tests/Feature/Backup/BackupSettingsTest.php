<?php

use App\Models\BackupSetting;
use Illuminate\Support\Facades\DB;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function backupSettingsPayload(array $overrides = []): array
{
    return array_merge([
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
    ], $overrides);
}

test('an authorized admin can update backup settings', function (): void {
    $user = superAdmin();

    $this->actingAs($user)
        ->put(route('backups.settings.update'), backupSettingsPayload(['retention_daily_days' => 21]))
        ->assertRedirect(route('backups.settings.edit'));

    expect(BackupSetting::query()->find(1)?->retention_daily_days)->toBe(21);

    expect(DB::table('audit_events')
        ->where('module', 'backups')
        ->where('action', 'backup_settings_updated')
        ->exists())->toBeTrue();
});

test('settings reject an enabled schedule with no backup type', function (): void {
    $user = superAdmin();

    $this->actingAs($user)
        ->put(route('backups.settings.update'), backupSettingsPayload([
            'database_enabled' => false,
            'full_enabled' => false,
        ]))
        ->assertSessionHasErrors('database_enabled');
});

test('settings reject an extraction limit below the upload limit', function (): void {
    $user = superAdmin();

    $this->actingAs($user)
        ->put(route('backups.settings.update'), backupSettingsPayload([
            'max_upload_megabytes' => 512,
            'max_expanded_megabytes' => 256,
        ]))
        ->assertSessionHasErrors('max_expanded_megabytes');
});
