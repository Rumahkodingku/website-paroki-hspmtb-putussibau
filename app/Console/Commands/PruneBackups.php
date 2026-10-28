<?php

namespace App\Console\Commands;

use App\Actions\Backup\PruneBackupsAction;
use Illuminate\Console\Command;

final class PruneBackups extends Command
{
    protected $signature = 'backups:prune';

    protected $description = 'Hapus backup yang melewati masa retensi';

    public function handle(PruneBackupsAction $action): int
    {
        $result = $action->handle();

        $this->components->info(sprintf(
            '%d backup dihapus, %d gagal dihapus, %d operasi pemulihan kedaluwarsa.',
            $result['deleted'],
            $result['failed'],
            $result['expired'],
        ));

        return self::SUCCESS;
    }
}
