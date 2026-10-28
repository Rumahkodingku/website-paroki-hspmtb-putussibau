<?php

namespace App\Enums;

enum BackupStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Validating = 'validating';
    case Success = 'success';
    case Failed = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return (string) __('backups.statuses.'.$this->value);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Queued, self::Running, self::Validating], true);
    }
}
