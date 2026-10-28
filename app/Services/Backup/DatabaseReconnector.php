<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;

/**
 * Reconnects the application connection after an external restore.
 *
 * It is a service so the restore job can be tested without purging the test
 * transaction, which would roll back the very records the test is asserting on.
 */
class DatabaseReconnector
{
    public function refresh(): void
    {
        DB::purge();
    }
}
