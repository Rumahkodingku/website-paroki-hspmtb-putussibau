<?php

namespace App\Services\Backup;

use App\Enums\BackupType;
use App\Exceptions\BackupException;
use JsonException;
use ZipArchive;

/**
 * Reads an archive without trusting it.
 *
 * The central directory is inspected before anything is extracted. Entries
 * outside the known layout, duplicate names, symlinks, encryption and expansion
 * bombs are all refused.
 */
final class BackupArchiveInspector
{
    public function __construct(
        private readonly BackupManifest $manifest,
        private readonly BackupPathGuard $paths,
    ) {}

    /**
     * @param  list<array{disk: string, path: string}>  $scopes
     * @param  array{max_expanded_bytes: int, max_files: int, max_compression_ratio: int}  $limits
     * @return array{
     *     manifest: array<string, mixed>,
     *     artifact_checksum: string,
     *     database_checksum: string,
     *     file_count: int,
     *     expanded_bytes: int
     * }
     */
    public function inspect(
        BackupType $type,
        string $absolutePath,
        array $scopes,
        array $limits,
    ): array {
        if (! is_file($absolutePath) || (int) filesize($absolutePath) === 0) {
            throw BackupException::because('archive_invalid');
        }

        return match ($type) {
            BackupType::Database => $this->inspectSqlGz($absolutePath, $limits),
            BackupType::Full => $this->inspectZip($absolutePath, $scopes, $limits),
        };
    }

    /**
     * @param  array{max_expanded_bytes: int, max_files: int, max_compression_ratio: int}  $limits
     * @return array{
     *     manifest: array<string, mixed>,
     *     artifact_checksum: string,
     *     database_checksum: string,
     *     file_count: int,
     *     expanded_bytes: int
     * }
     */
    public function inspectSqlGz(string $path, array $limits): array
    {
        if (! str_ends_with(strtolower($path), '.sql.gz')) {
            throw BackupException::because('format_unsupported');
        }

        $handle = gzopen($path, 'rb');

        if ($handle === false) {
            throw BackupException::because('archive_invalid');
        }

        $databaseChecksum = '';
        $expanded = 0;

        try {
            $manifestLine = gzgets($handle);

            if (! is_string($manifestLine)) {
                throw BackupException::because('archive_invalid');
            }

            $manifest = $this->manifest->decodeSqlComment($manifestLine);
            $this->manifest->assertSupported($manifest, BackupType::Database);

            $context = hash_init('sha256');
            $header = '';
            $tail = '';
            $expanded = 0;

            while (! gzeof($handle)) {
                $chunk = gzread($handle, 1024 * 1024);

                if ($chunk === false) {
                    throw BackupException::because('archive_invalid');
                }

                if ($chunk === '') {
                    continue;
                }

                $expanded += strlen($chunk);

                if ($expanded > $limits['max_expanded_bytes']) {
                    throw BackupException::because('size_limit');
                }

                hash_update($context, $chunk);
                $tail = substr($tail.$chunk, -4096);

                if ($header === '') {
                    $header = substr($chunk, 0, 4096);
                }
            }

            $databaseChecksum = hash_final($context);
        } finally {
            gzclose($handle);
        }

        $expected = (string) ($manifest['checksums']['database_sha256'] ?? '');

        if (! hash_equals($expected, $databaseChecksum)) {
            throw BackupException::because('checksum_mismatch');
        }

        if (preg_match('/^-- (MySQL|MariaDB) dump/m', ltrim($header)) !== 1) {
            throw BackupException::because('archive_invalid');
        }

        if (! str_contains($tail, '-- Dump completed')) {
            throw BackupException::because('archive_invalid');
        }

        return [
            'manifest' => $manifest,
            'artifact_checksum' => (string) hash_file('sha256', $path),
            'database_checksum' => $databaseChecksum,
            'file_count' => 0,
            'expanded_bytes' => $expanded,
        ];
    }

    /**
     * @param  list<array{disk: string, path: string}>  $scopes
     * @param  array{max_expanded_bytes: int, max_files: int, max_compression_ratio: int}  $limits
     * @return array{
     *     manifest: array<string, mixed>,
     *     artifact_checksum: string,
     *     database_checksum: string,
     *     file_count: int,
     *     expanded_bytes: int
     * }
     */
    public function inspectZip(string $path, array $scopes, array $limits): array
    {
        if (! str_ends_with(strtolower($path), '.zip')) {
            throw BackupException::because('format_unsupported');
        }

        $zip = new ZipArchive;
        $opened = $zip->open($path, ZipArchive::RDONLY);

        if ($opened !== true) {
            throw BackupException::because('archive_invalid');
        }

        $manifest = [];
        $databaseChecksum = '';
        $expanded = 0;
        $included = [];

        try {
            $count = $zip->numFiles;

            if ($count < 2 || $count > $limits['max_files']) {
                throw BackupException::because('size_limit');
            }

            $names = [];
            $entries = [];
            $manifestRaw = null;
            $expanded = 0;
            $files = 0;

            for ($index = 0; $index < $count; $index++) {
                $stat = $zip->statIndex($index);

                if (! is_array($stat)) {
                    throw BackupException::because('archive_invalid');
                }

                $name = (string) $stat['name'];

                if ($name === '') {
                    throw BackupException::because('archive_invalid');
                }

                $isDirectory = str_ends_with($name, '/');
                $normalised = $this->paths->assertSafeRelative(rtrim($name, '/'));

                if (isset($names[$normalised])) {
                    throw BackupException::because('archive_invalid');
                }

                $names[$normalised] = true;

                $opsys = 0;
                $attributes = 0;

                if ($zip->getExternalAttributesIndex($index, $opsys, $attributes)
                    && $opsys === ZipArchive::OPSYS_UNIX
                    && (($attributes >> 16) & 0170000) === 0120000) {
                    throw BackupException::because('path_unsafe');
                }

                if ($stat['encryption_method'] !== 0) {
                    throw BackupException::because('archive_invalid');
                }

                if ($isDirectory) {
                    continue;
                }

                $files++;
                $size = (int) $stat['size'];
                $compressed = (int) $stat['comp_size'];

                if ($compressed > 0 && $size > 0
                    && ($size / $compressed) > $limits['max_compression_ratio']) {
                    throw BackupException::because('size_limit');
                }

                $expanded += $size;

                if ($expanded > $limits['max_expanded_bytes']) {
                    throw BackupException::because('size_limit');
                }

                if (! $this->isAllowedEntry($normalised)) {
                    throw BackupException::because('path_unsafe');
                }

                $entries[$normalised] = $size;

                if ($normalised === 'manifest.json') {
                    $raw = $zip->getFromIndex($index);
                    $manifestRaw = is_string($raw) ? $raw : null;
                }
            }

            if (! is_string($manifestRaw)) {
                throw BackupException::because('format_unsupported');
            }

            try {
                $manifest = json_decode($manifestRaw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw BackupException::because('archive_invalid');
            }

            if (! is_array($manifest)) {
                throw BackupException::because('archive_invalid');
            }

            $this->manifest->assertSupported($manifest, BackupType::Full);

            if (! isset($entries['database/database.sql'])) {
                throw BackupException::because('archive_invalid');
            }

            $expected = ['manifest.json' => true, 'database/database.sql' => true];
            $included = $manifest['files']['included'] ?? null;

            if (! is_array($included)) {
                throw BackupException::because('archive_invalid');
            }

            foreach ($included as $file) {
                if (! is_array($file)) {
                    throw BackupException::because('archive_invalid');
                }

                $disk = (string) ($file['disk'] ?? '');
                $relative = (string) ($file['path'] ?? '');
                $relative = $this->paths->assertAllowedScope($disk, $relative, $scopes);
                $entry = 'files/'.$disk.'/'.$relative;

                if (! isset($entries[$entry])) {
                    throw BackupException::because('archive_invalid');
                }

                if ((int) ($file['size'] ?? -1) !== $entries[$entry]) {
                    throw BackupException::because('archive_invalid');
                }

                $expected[$entry] = true;

                $stream = $zip->getStream($entry);

                if (! is_resource($stream)) {
                    throw BackupException::because('archive_invalid');
                }

                $context = hash_init('sha256');

                try {
                    hash_update_stream($context, $stream);
                    $actual = hash_final($context);
                } finally {
                    fclose($stream);
                }

                $expectedHash = (string) ($file['sha256'] ?? '');

                if (! hash_equals($expectedHash, $actual)) {
                    throw BackupException::because('checksum_mismatch');
                }
            }

            foreach (array_keys($entries) as $entry) {
                if (! isset($expected[$entry])) {
                    throw BackupException::because('path_unsafe');
                }
            }

            $databaseStream = $zip->getStream('database/database.sql');

            if (! is_resource($databaseStream)) {
                throw BackupException::because('archive_invalid');
            }

            $databaseContext = hash_init('sha256');

            try {
                hash_update_stream($databaseContext, $databaseStream);
                $databaseChecksum = hash_final($databaseContext);
            } finally {
                fclose($databaseStream);
            }

            $expectedDatabase = (string) ($manifest['checksums']['database_sha256'] ?? '');

            if (! hash_equals($expectedDatabase, $databaseChecksum)) {
                throw BackupException::because('checksum_mismatch');
            }
        } finally {
            $zip->close();
        }

        return [
            'manifest' => $manifest,
            'artifact_checksum' => (string) hash_file('sha256', $path),
            'database_checksum' => $databaseChecksum,
            'file_count' => count($included),
            'expanded_bytes' => $expanded,
        ];
    }

    private function isAllowedEntry(string $name): bool
    {
        return $name === 'manifest.json'
            || $name === 'database/database.sql'
            || str_starts_with($name, 'files/');
    }
}
