<?php

use App\Actions\Backup\ReconcileBackupsAction;
use App\Actions\Backup\VerifyBackupsAction;
use App\Models\Backup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('backups');
});

test('verification marks a missing file and clears it when the file returns', function (): void {
    $backup = Backup::factory()->create([
        'disk' => 'backups',
        'path' => 'archives/'.Str::uuid().'/backup.sql.gz',
        'file_size' => 8,
    ]);
    Storage::disk('backups')->put((string) $backup->path, 'artifact');
    Storage::disk('backups')->delete((string) $backup->path);

    $result = app(VerifyBackupsAction::class)->handle();

    expect($result['missing'])->toBe(1)
        ->and($backup->fresh()->file_missing_at)->not->toBeNull();

    Storage::disk('backups')->put((string) $backup->path, 'artifact');

    $result = app(VerifyBackupsAction::class)->handle();

    expect($result['restored'])->toBe(1)
        ->and($backup->fresh()->file_missing_at)->toBeNull();
});

test('reconciliation reports and optionally removes orphan files', function (): void {
    $backup = Backup::factory()->create([
        'disk' => 'backups',
        'path' => 'archives/known/backup.sql.gz',
    ]);
    Storage::disk('backups')->put('archives/known/backup.sql.gz', 'known');
    Storage::disk('backups')->put('archives/orphan/backup.sql.gz', 'orphan');

    $result = app(ReconcileBackupsAction::class)->handle();

    expect($result['orphans'])->toContain('archives/orphan/backup.sql.gz')
        ->and($result['deleted'])->toBe(0);

    $result = app(ReconcileBackupsAction::class)->handle(true);

    expect($result['deleted'])->toBe(1)
        ->and(Storage::disk('backups')->exists('archives/orphan/backup.sql.gz'))->toBeFalse();
});
