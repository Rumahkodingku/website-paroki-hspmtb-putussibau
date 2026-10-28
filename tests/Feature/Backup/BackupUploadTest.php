<?php

use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Models\Backup;
use App\Services\Backup\BackupManifest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('backups');
});

function makeUploadFixture(bool $validChecksum = true): UploadedFile
{
    $dump = "-- MySQL dump 10.13\nCREATE TABLE contoh (id int);\n-- Dump completed on 2026-10-28\n";
    $checksum = $validChecksum ? hash('sha256', $dump) : str_repeat('0', 64);
    $manifest = app(BackupManifest::class)->build(
        BackupType::Database,
        (string) Str::uuid(),
        Carbon::now(),
        'system',
        null,
        'Penguji',
        null,
        $checksum,
    );

    $path = sys_get_temp_dir().'/'.Str::uuid().'.sql.gz';
    $handle = gzopen($path, 'wb9');

    if ($handle === false) {
        throw new RuntimeException('Gzip fixture could not be created.');
    }

    gzwrite($handle, app(BackupManifest::class)->sqlComment($manifest)."\n".$dump);
    gzclose($handle);

    return new UploadedFile($path, 'backup.sql.gz', 'application/gzip', null, true);
}

test('a valid external archive is accepted and never restored', function (): void {
    Queue::fake();
    $user = superAdmin();

    $response = $this->actingAs($user)->post(route('backups.upload'), [
        'file' => makeUploadFixture(),
    ]);

    $response->assertRedirect();

    $backup = Backup::query()->latest('id')->firstOrFail();

    expect($backup->status)->toBe(BackupStatus::Success)
        ->and($backup->validation_status)->toBe(BackupValidationStatus::Passed)
        ->and($backup->source->value)->toBe('uploaded')
        ->and($backup->path)->not->toBeNull()
        ->and(Storage::disk('backups')->exists((string) $backup->path))->toBeTrue();

    Queue::assertNothingPushed();
});

test('an invalid external archive is rejected and removed', function (): void {
    $user = superAdmin();

    $response = $this->actingAs($user)->post(route('backups.upload'), [
        'file' => makeUploadFixture(false),
    ]);

    $response->assertSessionHasErrors('file');

    $backup = Backup::query()->latest('id')->firstOrFail();

    expect($backup->status)->toBe(BackupStatus::Failed)
        ->and($backup->validation_status)->toBe(BackupValidationStatus::Failed)
        ->and($backup->path)->toBeNull()
        ->and(Storage::disk('backups')->allFiles())->toBe([]);
});

test('an upload with a misleading extension is rejected', function (): void {
    $user = superAdmin();
    $file = UploadedFile::fake()->createWithContent('backup.zip', 'not a zip');

    $this->actingAs($user)->post(route('backups.upload'), ['file' => $file])
        ->assertSessionHasErrors('file');
});
