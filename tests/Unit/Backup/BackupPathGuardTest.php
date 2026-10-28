<?php

use App\Exceptions\BackupException;
use App\Services\Backup\BackupPathGuard;

beforeEach(function (): void {
    $this->guard = new BackupPathGuard;
});

test('it accepts a normal relative path', function (): void {
    expect($this->guard->assertSafeRelative('media/originals/foto.jpg'))
        ->toBe('media/originals/foto.jpg');
});

test('it rejects traversal and absolute paths', function (string $path): void {
    expect(fn () => $this->guard->assertSafeRelative($path))
        ->toThrow(BackupException::class);
})->with([
    '../etc/passwd',
    'media/../../etc/passwd',
    '/etc/passwd',
    'C:\\Windows\\system32',
    '\\\\server\\share\\file',
    "media/\0file",
    'media//file',
    './media/file',
    'media/./file',
]);

test('it accepts paths inside an allowed scope only', function (): void {
    $scopes = [['disk' => 'local', 'path' => 'media/originals']];

    expect($this->guard->assertAllowedScope('local', 'media/originals/a.jpg', $scopes))
        ->toBe('media/originals/a.jpg');

    expect(fn () => $this->guard->assertAllowedScope('public', 'media/originals/a.jpg', $scopes))
        ->toThrow(BackupException::class);

    expect(fn () => $this->guard->assertAllowedScope('local', 'documents/a.pdf', $scopes))
        ->toThrow(BackupException::class);
});
