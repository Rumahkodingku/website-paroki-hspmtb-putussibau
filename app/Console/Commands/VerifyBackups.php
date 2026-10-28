<?php

namespace App\Console\Commands;

use App\Actions\Backup\VerifyBackupsAction;
use Illuminate\Console\Command;

final class VerifyBackups extends Command
{
    protected $signature = 'backups:verify';

    protected $description = 'Periksa file backup yang hilang atau berubah ukuran';

    public function handle(VerifyBackupsAction $action): int
    {
        $result = $action->handle();

        $this->components->info(sprintf(
            '%d file hilang, %d berubah, %d tersedia kembali.',
            $result['missing'],
            $result['changed'],
            $result['restored'],
        ));

        return self::SUCCESS;
    }
}
