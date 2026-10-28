<?php

namespace App\Jobs;

use App\Services\Backup\BackupHealth;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Proves that a worker is consuming the backup queue.
 *
 * The settings page treats a stale heartbeat as "not detected", so a queue
 * that is configured but has no worker is not presented as ready.
 */
class BackupWorkerHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function handle(BackupHealth $health): void
    {
        $health->markWorkerHeartbeat();
    }
}
