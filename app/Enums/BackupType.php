<?php

namespace App\Enums;

enum BackupType: string
{
    case Database = 'database';
    case Full = 'full';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return (string) __('backups.types.'.$this->value);
    }
}
