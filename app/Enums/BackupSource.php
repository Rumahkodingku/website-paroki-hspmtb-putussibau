<?php

namespace App\Enums;

enum BackupSource: string
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';
    case Uploaded = 'uploaded';
    case PreRestore = 'pre_restore';

    public function label(): string
    {
        return (string) __('backups.sources.'.$this->value);
    }
}
