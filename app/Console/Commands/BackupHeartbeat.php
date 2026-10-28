<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupHealth;
use Illuminate\Console\Command;

final class BackupHeartbeat extends Command
{
    protected $signature = 'backups:heartbeat';

    protected $description = 'Tulis heartbeat scheduler untuk status kesiapan backup';

    public function handle(BackupHealth $health): int
    {
        $health->markSchedulerHeartbeat();

        return self::SUCCESS;
    }
}
