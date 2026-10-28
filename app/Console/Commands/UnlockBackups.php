<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class UnlockBackups extends Command
{
    protected $signature = 'backups:unlock {--force : Jangan minta konfirmasi}';

    protected $description = 'Bersihkan lock dan flag maintenance backup yang macet';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Bersihkan lock backup dan flag pemulihan?')) {
            return self::SUCCESS;
        }

        Cache::lock('backups:exclusive')->forceRelease();
        Cache::forget(BackupHealth::RESTORE_FLAG);

        $this->components->info('Lock backup dan flag pemulihan dibersihkan.');

        return self::SUCCESS;
    }
}
