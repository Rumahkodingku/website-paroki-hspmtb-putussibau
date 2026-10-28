<?php

use App\Enums\BackupType;
use App\Exceptions\BackupException;
use App\Services\Backup\BackupManifest;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->manifest = new BackupManifest;
});

test('it builds a database manifest that can travel in a SQL comment', function (): void {
    $built = $this->manifest->build(
        BackupType::Database,
        '0199c1f4-7e2a-7f3b-9a11-4b4f2f3c1a2b',
        Carbon::parse('2026-10-28 02:00:00', 'UTC'),
        'user',
        7,
        'Super Admin',
        'Catatan',
        str_repeat('a', 64),
    );

    $this->manifest->assertSupported($built, BackupType::Database);

    $decoded = $this->manifest->decodeSqlComment(
        $this->manifest->sqlComment($built),
    );

    expect($decoded['type'])->toBe('database')
        ->and($decoded['backup_id'])->toBe('0199c1f4-7e2a-7f3b-9a11-4b4f2f3c1a2b')
        ->and($decoded['files']['count'])->toBe(0)
        ->and($decoded['created_by']['id'])->toBe(7);
});

test('it rejects an unsupported format version', function (): void {
    $built = $this->manifest->build(
        BackupType::Database,
        'id',
        Carbon::now(),
        'system',
        null,
        null,
        null,
        str_repeat('b', 64),
    );
    $built['format_version'] = '99';

    expect(fn () => $this->manifest->assertSupported($built))
        ->toThrow(BackupException::class);
});

test('it rejects a malformed comment', function (): void {
    expect(fn () => $this->manifest->decodeSqlComment('-- not-a-manifest'))
        ->toThrow(BackupException::class);
});
