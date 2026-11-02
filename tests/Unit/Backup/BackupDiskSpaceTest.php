<?php

use App\Services\Backup\BackupCreator;

it('requires twice the source and dump plus the threshold', function () {
    $gigabyte = 1024 ** 3;

    expect(BackupCreator::requiredBytes(0, $gigabyte))->toBe($gigabyte)
        ->and(BackupCreator::requiredBytes($gigabyte, $gigabyte))->toBe(3 * $gigabyte)
        ->and(BackupCreator::requiredBytes(3 * $gigabyte, 0))->toBe(6 * $gigabyte)
        ->and(BackupCreator::requiredBytes(0, $gigabyte, $gigabyte))->toBe(3 * $gigabyte)
        ->and(BackupCreator::requiredBytes($gigabyte, $gigabyte, $gigabyte))->toBe(5 * $gigabyte);
});
