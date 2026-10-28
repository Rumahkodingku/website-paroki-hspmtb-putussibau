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

        foreach ($scopes as $scope) {
            $prefix = trim($scope['path'], '/');

            if ($scope['disk'] === $disk && $this->isWithin($prefix, $path)) {
                return $path;
            }
        }

        throw BackupException::because('path_unsafe');
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
