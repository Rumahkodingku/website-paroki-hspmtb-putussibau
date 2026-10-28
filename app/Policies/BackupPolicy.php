<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;

/**
 * Who may see, create, download, upload, restore and delete backups.
 *
 * Every ability is separate. Holding `backups.upload` does not grant
 * `backups.restore`, and holding `backups.download` does not either.
 */
class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('backups.view');
    }

    public function view(User $user, Backup $backup): bool
    {
        return $user->can('backups.view');
    }

    public function create(User $user): bool
    {
        return $user->can('backups.create');
    }

    public function download(User $user, Backup $backup): bool
    {
        return $user->can('backups.download') && $backup->isDownloadable();
    }

    public function upload(User $user): bool
    {
        return $user->can('backups.upload');
    }

    public function restore(User $user, Backup $backup): bool
    {
        return $user->can('backups.restore')
            && $backup->isRestorable();
    }

    public function delete(User $user, Backup $backup): bool
    {
        return $user->can('backups.delete')
            && ! $backup->restores()->active()->exists();
    }

    public function settings(User $user): bool
    {
        return $user->can('backups.settings');
    }
}
