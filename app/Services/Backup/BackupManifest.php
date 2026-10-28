<?php

namespace App\Services\Backup;

use App\Enums\BackupType;
use App\Exceptions\BackupException;
use Carbon\CarbonInterface;

/**
 * Builds and reads the manifest that travels with every archive.
 *
 * A database backup has no room for a sibling file, so its manifest is the
 * first line of the SQL dump, encoded as a comment. MySQL ignores `--` lines,
 * which keeps the artifact restorable with the standard client.
 */
final class BackupManifest
{
    public const COMMENT_PREFIX = '-- HSPMTB-MANIFEST:';

    /**
     * @param  list<array{disk: string, path: string, size: int, sha256: string}>  $files
     * @return array<string, mixed>
     */
    public function build(
        BackupType $type,
        string $backupId,
        CarbonInterface $createdAt,
        string $creatorType,
        ?int $creatorId,
        ?string $creatorLabel,
        ?string $note,
        ?string $databaseChecksum,
        array $files = [],
        ?string $serverVersion = null,
    ): array {
        $total = array_sum(array_map(fn (array $file): int => (int) $file['size'], $files));

        return [
            'format' => (string) config('backups.format', 'hspmtb-backup'),
            'format_version' => (string) config('backups.format_version', '1'),
            'backup_id' => $backupId,
            'type' => $type->value,
            'created_at' => $createdAt->utc()->toIso8601String(),
            'app' => [
                'name' => (string) config('app.name'),
                'version' => config('backups.app_version'),
                'commit' => config('backups.app_commit'),
            ],
            'database' => [
                'driver' => (string) config('database.default'),
                'dump_path' => $type === BackupType::Full ? 'database/database.sql' : null,
                'server_version' => $serverVersion,
            ],
            'files' => [
                'included' => $files,
                'count' => count($files),
                'total_uncompressed_bytes' => $total,
            ],
            'checksums' => [
                'algorithm' => 'sha256',
                'database_sha256' => $databaseChecksum,
            ],
            'created_by' => [
                'type' => $creatorType,
                'id' => $creatorId,
                'label' => $creatorLabel,
            ],
            'note' => $note,
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function encode(array $manifest): string
    {
        $json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($json)) {
            throw BackupException::because('archive_invalid');
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function sqlComment(array $manifest): string
    {
        return self::COMMENT_PREFIX.base64_encode($this->encode($manifest));
    }

    /**
     * @return array<string, mixed>
     */
    public function decodeSqlComment(string $line): array
    {
        $line = trim($line);

        if (! str_starts_with($line, self::COMMENT_PREFIX)) {
            throw BackupException::because('format_unsupported');
        }

        $encoded = substr($line, strlen(self::COMMENT_PREFIX));
        $json = base64_decode($encoded, true);

        if ($json === false) {
            throw BackupException::because('archive_invalid');
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw BackupException::because('archive_invalid');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function assertSupported(array $manifest, ?BackupType $expected = null): void
    {
        if (($manifest['format'] ?? null) !== (string) config('backups.format', 'hspmtb-backup')) {
            throw BackupException::because('format_unsupported');
        }

        if ((string) ($manifest['format_version'] ?? '') !== (string) config('backups.format_version', '1')) {
            throw BackupException::because('format_unsupported');
        }

        $type = BackupType::tryFrom((string) ($manifest['type'] ?? ''));

        if ($type === null) {
            throw BackupException::because('format_unsupported');
        }

        if ($expected !== null && $type !== $expected) {
            throw BackupException::because('format_unsupported');
        }

        if (! is_string($manifest['backup_id'] ?? null) || $manifest['backup_id'] === '') {
            throw BackupException::because('archive_invalid');
        }

        if (! is_string($manifest['created_at'] ?? null)) {
            throw BackupException::because('archive_invalid');
        }

        $checksums = $manifest['checksums'] ?? null;

        if (! is_array($checksums) || ($checksums['algorithm'] ?? null) !== 'sha256') {
            throw BackupException::because('archive_invalid');
        }

        if (! is_string($checksums['database_sha256'] ?? null)) {
            throw BackupException::because('archive_invalid');
        }

        if ($type === BackupType::Full) {
            $files = $manifest['files'] ?? null;

            if (! is_array($files) || ! is_array($files['included'] ?? null)) {
                throw BackupException::because('archive_invalid');
            }
        }
    }
}
