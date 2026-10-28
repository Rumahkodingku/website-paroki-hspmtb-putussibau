<?php

namespace App\Services\Backup;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Reports what the deployment can actually do right now.
 *
 * A missing heartbeat means "not detected", never "assume active". The settings
 * page uses this so an operator is not told that automatic backups work when
 * the scheduler or worker has never run.
 */
final class BackupHealth
{
    public const SCHEDULER_HEARTBEAT = 'backups:heartbeat:scheduler';

    public const WORKER_HEARTBEAT = 'backups:heartbeat:worker';

    public const RESTORE_FLAG = 'backups:restore-in-progress';

    /**
     * @return array{ready: bool, last_seen_at: string|null}
     */
    public function schedulerStatus(): array
    {
        return $this->heartbeatStatus(
            self::SCHEDULER_HEARTBEAT,
            (int) config('backups.heartbeat.scheduler_ttl_seconds', 300),
        );
    }

    /**
     * @return array{ready: bool, last_seen_at: string|null}
     */
    public function workerStatus(): array
    {
        return $this->heartbeatStatus(
            self::WORKER_HEARTBEAT,
            (int) config('backups.heartbeat.worker_ttl_seconds', 900),
        );
    }

    public function queueReady(): bool
    {
        return config('backups.queue_connection') === 'sync' || $this->workerStatus()['ready'];
    }

    /**
     * @return array{mysqldump: bool, mysql: bool, ready: bool}
     */
    public function binaryStatus(): array
    {
        $finder = new ExecutableFinder;
        $dump = $finder->find((string) config('backups.mysqldump_path'));
        $mysql = $finder->find((string) config('backups.mysql_path'));

        return [
            'mysqldump' => $dump !== null,
            'mysql' => $mysql !== null,
            'ready' => $dump !== null && $mysql !== null,
        ];
    }

    /**
     * @return array{upload_max_filesize: int, post_max_size: int, effective: int}
     */
    public function uploadLimit(): array
    {
        $upload = $this->parseBytes((string) ini_get('upload_max_filesize'));
        $post = $this->parseBytes((string) ini_get('post_max_size'));

        $values = array_values(array_filter(
            [$upload, $post],
            fn (int $value): bool => $value > 0,
        ));

        return [
            'upload_max_filesize' => $upload,
            'post_max_size' => $post,
            'effective' => $values === [] ? 0 : min($values),
        ];
    }

    /**
     * @return array{local: bool, free_bytes: int|null, low: bool}
     */
    public function storageStatus(): array
    {
        $disk = (string) config('backups.disk');
        $driver = (string) config("filesystems.disks.{$disk}.driver", 'local');
        $free = null;

        if ($driver === 'local') {
            $root = (string) config("filesystems.disks.{$disk}.root", storage_path('app/private/backups'));
            $free = @disk_free_space($root);
            $free = $free === false ? null : (int) $free;
        }

        $threshold = (int) app(BackupSettingsService::class)->values()['low_disk_threshold_megabytes'] * 1024 * 1024;

        return [
            'local' => $driver === 'local',
            'free_bytes' => $free,
            'low' => $free !== null && $free < $threshold,
        ];
    }

    /**
     * @return array{
     *     scheduler: array{ready: bool, last_seen_at: string|null},
     *     worker: array{ready: bool, last_seen_at: string|null},
     *     queue_ready: bool,
     *     binary: array{mysqldump: bool, mysql: bool, ready: bool},
     *     storage: array{local: bool, free_bytes: int|null, low: bool},
     *     upload_limit: array{upload_max_filesize: int, post_max_size: int, effective: int}
     * }
     */
    public function readiness(): array
    {
        return [
            'scheduler' => $this->schedulerStatus(),
            'worker' => $this->workerStatus(),
            'queue_ready' => $this->queueReady(),
            'binary' => $this->binaryStatus(),
            'storage' => $this->storageStatus(),
            'upload_limit' => $this->uploadLimit(),
        ];
    }

    public function markSchedulerHeartbeat(): void
    {
        Cache::put(self::SCHEDULER_HEARTBEAT, Carbon::now()->toIso8601String(), 86400);
    }

    public function markWorkerHeartbeat(): void
    {
        Cache::put(self::WORKER_HEARTBEAT, Carbon::now()->toIso8601String(), 86400);
    }

    /**
     * @return array{ready: bool, last_seen_at: string|null}
     */
    private function heartbeatStatus(string $key, int $ttl): array
    {
        $lastSeen = Cache::get($key);

        if (! is_string($lastSeen)) {
            return ['ready' => false, 'last_seen_at' => null];
        }

        try {
            $at = Carbon::parse($lastSeen);
        } catch (\Throwable) {
            return ['ready' => false, 'last_seen_at' => null];
        }

        return [
            'ready' => $at->diffInSeconds(Carbon::now()) <= $ttl,
            'last_seen_at' => $at->toIso8601String(),
        ];
    }

    private function parseBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
