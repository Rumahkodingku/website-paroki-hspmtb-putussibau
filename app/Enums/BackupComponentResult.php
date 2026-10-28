<?php

namespace App\Enums;

enum BackupComponentResult: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return (string) __('backups.components.'.$this->value);
    }
}
