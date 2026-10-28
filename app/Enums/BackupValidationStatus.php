<?php

namespace App\Enums;

enum BackupValidationStatus: string
{
    case Pending = 'pending';
    case Passed = 'passed';
    case Failed = 'failed';

    public function label(): string
    {
        return (string) __('backups.validation.'.$this->value);
    }
}
