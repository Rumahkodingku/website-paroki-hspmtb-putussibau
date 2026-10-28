<?php

namespace App\Enums;

enum BackupRestoreStatus: string
{
    case Queued = 'queued';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Running = 'running';
    case Success = 'success';
    case Failed = 'failed';
    case Partial = 'partial';
    case Expired = 'expired';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return (string) __('backups.restore_statuses.'.$this->value);
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Queued,
            self::Preparing,
            self::Ready,
            self::Running,
        ], true);
    }
}
