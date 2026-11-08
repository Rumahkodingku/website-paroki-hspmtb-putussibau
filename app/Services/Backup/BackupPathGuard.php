<?php

namespace App\Services\Backup;

use App\Exceptions\BackupException;

/**
 * Turns a relative archive path into one that is safe to write.
 *
 * Rejects traversal, absolute paths, null bytes and special filesystem entries.
 * Callers still have to keep the destination root fixed; this class never
 * decides where a file should go.
 */
final class BackupPathGuard
{
    public function assertSafeRelative(string $path): string
    {
        if ($path === '' || $path !== trim($path)) {
            throw BackupException::because('path_unsafe');
        }

        if (str_contains($path, "\0") || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            throw BackupException::because('path_unsafe');
        }

        if (str_contains($path, '\\')) {
            throw BackupException::because('path_unsafe');
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) === 1) {
            throw BackupException::because('path_unsafe');
        }

        if (str_contains($path, '//')) {
            throw BackupException::because('path_unsafe');
        }

        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw BackupException::because('path_unsafe');
            }
        }

        return implode('/', $segments);
    }

    /**
     * @param  list<array{disk: string, path: string}>  $scopes
     */
    public function assertAllowedScope(string $disk, string $path, array $scopes): string
    {
        $path = $this->assertSafeRelative($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        foreach ($scopes as $scope) {
            $prefix = trim($scope['path'], '/');

            if ($scope['disk'] === $disk && $this->isWithin($prefix, $path)) {
                if (! $this->hasAllowedExtensionForScope($disk, $prefix, $extension)) {
                    throw BackupException::because('path_unsafe');
                }

                return $path;
            }
        }

        throw BackupException::because('path_unsafe');
    }

    private function hasAllowedExtensionForScope(string $disk, string $prefix, string $extension): bool
    {
        if ($extension === '') {
            return false;
        }

        $variantDisk = (string) config('media.variant_disk');
        $variantPath = trim((string) config('media.paths.variants'), '/');

        if ($disk === $variantDisk && $prefix === $variantPath) {
            return $extension === 'webp';
        }

        $originalDisk = (string) config('media.original_disk');
        $originalPath = trim((string) config('media.paths.original'), '/');

        if ($disk === $originalDisk && $prefix === $originalPath) {
            return in_array($extension, ['jpg', 'jpeg', 'png'], true);
        }

        $documentDisk = (string) config('documents.disk');
        $documentPath = trim((string) config('documents.path'), '/');

        if ($disk === $documentDisk && $prefix === $documentPath) {
            $allowed = array_values(array_unique((array) config('documents.extensions', [])));

            return in_array($extension, $allowed, true);
        }

        return false;
    }

    public function assertInsideRoot(string $root, string $path): string
    {
        $realRoot = realpath($root);
        $realPath = realpath($path);

        if ($realRoot === false || $realPath === false) {
            throw BackupException::because('path_unsafe');
        }

        if (! $this->isWithin($realRoot, $realPath)) {
            throw BackupException::because('path_unsafe');
        }

        if (is_link($realPath)) {
            throw BackupException::because('path_unsafe');
        }

        return $realPath;
    }

    public function assertNotSymlink(string $path): void
    {
        if (is_link($path)) {
            throw BackupException::because('path_unsafe');
        }
    }

    private function isWithin(string $prefix, string $path): bool
    {
        if ($prefix === '' || $prefix === '.') {
            return true;
        }

        return $path === $prefix || str_starts_with($path, $prefix.'/');
    }
}
