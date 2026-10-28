<?php

namespace App\Actions\Backup;

use App\Models\BackupSetting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Backup\BackupSettingsService;

final class UpdateBackupSettingsAction
{
    public function __construct(
        private readonly BackupSettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public function handle(array $values, User $actor): BackupSetting
    {
        $before = $this->audit->snapshot($this->settings->model(), 'backups');
        $updated = $this->settings->update($values, $actor);

        $this->audit->updated(
            'backups',
            $updated,
            $before,
            'backup_settings_updated',
            null,
            ['setting_keys_changed' => array_keys($values)],
        );

        return $updated;
    }
}
