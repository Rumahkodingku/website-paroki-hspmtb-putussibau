<?php

namespace App\Services\Backup;

use App\Enums\BackupType;
use App\Exceptions\BackupException;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Every filesystem path a backup touches goes through this class.
 *
 * Final archives live on the configured private disk. Temp files, upload
 * quarantine and restore staging live locally so a large dump is never uploaded
 * halfway through an operation.
 */
final class BackupStorage
{
    public function disk(): Filesystem
    {
        return Storage::disk((string) config('backups.disk'));
    }

    public function tempRoot(): string
    {
        return (string) config('backups.temp_path');
    }

    public function tempPath(string $uuid, string $name = ''): string
    {
        $directory = $this->tempRoot().'/'.$uuid;
        File::ensureDirectoryExists($directory, 0700, true);

        return $name === '' ? $directory : $directory.'/'.$name;
    }

    public function stagingPath(string $uuid): string
    {
        $directory = $this->tempPath($uuid).'/staging';
        File::ensureDirectoryExists($directory, 0700, true);

        return $directory;
    }

    public function archivePath(string $uuid, string $filename): string
    {
        return trim((string) config('backups.archive_path'), '/').'/'.$uuid.'/'.$filename;
    }

    public function uploadPath(string $uuid): string
    {
        return trim((string) config('backups.upload_path'), '/').'/'.$uuid;
    }

    public function storeUpload(UploadedFile $file, string $uuid): string
    {
        $path = $file->storeAs($this->uploadPath($uuid), 'upload.bin', (string) config('backups.disk'));

        if (! is_string($path)) {
            throw BackupException::because('storage_unavailable');
        }

        return $path;
    }

    public function moveToArchive(string $localPath, string $relativePath): void
    {
        $stream = fopen($localPath, 'rb');

        if ($stream === false) {
            throw BackupException::because('storage_unavailable');
        }

        try {
            $written = $this->disk()->put($relativePath, $stream);

            if (! $written) {
                throw BackupException::because('storage_unavailable');
            }
        } finally {
            fclose($stream);
        }

        File::delete($localPath);
    }

    public function copyToArchive(string $fromRelative, string $toRelative): void
    {
        if (! $this->disk()->copy($fromRelative, $toRelative)) {
            throw BackupException::because('storage_unavailable');
        }
    }

    public function delete(string $relativePath): bool
    {
        if (! $this->disk()->exists($relativePath)) {
            return true;
        }

        return $this->disk()->delete($relativePath);
    }

    public function exists(string $relativePath): bool
    {
        return $this->disk()->exists($relativePath);
    }

    public function size(string $relativePath): ?int
    {
        try {
            return $this->disk()->size($relativePath);
        } catch (\Throwable) {
            return null;
        }
    }

    public function checksum(string $relativePath): string
    {
        $stream = $this->disk()->readStream($relativePath);

        if (! is_resource($stream)) {
            throw BackupException::because('storage_unavailable');
        }

        try {
            return $this->hashStream($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Copy a final artifact to a local file for validation or staging.
     */
    public function localCopy(string $relativePath, string $localPath): void
    {
        $stream = $this->disk()->readStream($relativePath);

        if (! is_resource($stream)) {
            throw BackupException::because('storage_unavailable');
        }

        $target = fopen($localPath, 'wb');

        if ($target === false) {
            fclose($stream);
            throw BackupException::because('storage_unavailable');
        }

        try {
            stream_copy_to_stream($stream, $target);
        } finally {
            fclose($stream);
            fclose($target);
        }
    }

    public function freeBytes(): ?int
    {
        $root = $this->tempRoot();
        File::ensureDirectoryExists($root);

        $free = @disk_free_space($root);

        return $free === false ? null : (int) $free;
    }

    public function cleanup(string $uuid): void
    {
        File::deleteDirectory($this->tempRoot().'/'.$uuid);
    }

    public function suggestedFilename(BackupType $type, CarbonInterface $at): string
    {
        $prefix = $type === BackupType::Database ? 'hspmtb-db' : 'hspmtb-full';
        $extension = $type === BackupType::Database ? 'sql.gz' : 'zip';

        return $prefix.'-'.$at->utc()->format('Y-m-d-His').'.'.$extension;
    }

    public function downloadFilename(?string $original, BackupType $type, CarbonInterface $at): string
    {
        $fallback = $this->suggestedFilename($type, $at);

        if ($original === null || trim($original) === '') {
            return $fallback;
        }

        $name = str_replace(['\\', '/', "\0"], '', $original);
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
        $name = trim($name);

        return $name === '' ? $fallback : Str::limit($name, 150, '');
    }

    /**
     * @param  resource  $stream
     */
    public function hashStream($stream): string
    {
        $context = hash_init('sha256');
        hash_update_stream($context, $stream);

        return hash_final($context);
    }
}
