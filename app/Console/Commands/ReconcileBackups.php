<?php

namespace App\Console\Commands;

use App\Actions\Backup\ReconcileBackupsAction;
use Illuminate\Console\Command;

final class ReconcileBackups extends Command
{
    protected $signature = 'backups:reconcile {--delete-orphans : Hapus file arsip yang tidak memiliki metadata}';

    protected $description = 'Laporkan file backup orphan atau metadata tanpa file';

    public function handle(ReconcileBackupsAction $action): int
    {
        $result = $action->handle((bool) $this->option('delete-orphans'));

        $this->components->info(sprintf(
            '%d orphan, %d metadata tanpa file, %d orphan dihapus.',
            count($result['orphans']),
            count($result['missing']),
            $result['deleted'],
        ));

        foreach ($result['orphans'] as $orphan) {
            $this->line('  orphan: '.$orphan);
        }

        foreach ($result['missing'] as $missing) {
            $this->line('  missing: '.$missing);
        }

        return self::SUCCESS;
    }
}
