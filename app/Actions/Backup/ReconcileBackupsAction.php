<?php

namespace App\Actions\Backup;

use App\Models\Backup;
use App\Services\AuditLogger;
use App\Services\Backup\BackupStorage;

final class ReconcileBackupsAction
{
    public function __construct(
        private readonly BackupStorage $storage,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{orphans: list<string>, missing: list<string>, deleted: int}
     */
    public function handle(bool $deleteOrphans = false): array
    {
        $prefix = trim((string) config('backups.archive_path'), '/');
        $files = $this->storage->disk()->allFiles($prefix);
        $known = Backup::query()->whereNotNull('path')->pluck('path')->all();
        $orphans = array_values(array_diff($files, $known));
        $missing = Backup::query()
            ->whereNotNull('path')
            ->whereNotIn('path', $files)
            ->pluck('uuid')
            ->map(fn (mixed $uuid): string => (string) $uuid)
            ->values()
            ->all();
        $deleted = 0;

        if ($deleteOrphans) {
            foreach ($orphans as $orphan) {
                if ($this->storage->delete($orphan)) {
                    $deleted++;
                }
            }

            if ($deleted > 0) {
                $this->audit->system(
                    'backups',
                    'backup_deleted',
                    'success',
                    __('audit.descriptions.backup_deleted'),
                    ['count' => $deleted],
                );
            }
        }

        return [
            'orphans' => $orphans,
            'missing' => array_values($missing),
            'deleted' => $deleted,
        ];
    }
}
