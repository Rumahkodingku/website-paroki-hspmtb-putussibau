<?php

use App\Enums\BackupRestoreStatus;
use App\Jobs\CreateBackupJob;
use App\Jobs\PrepareRestoreJob;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('backups');
    config(['backups.queue_connection' => 'sync']);
});

function backupUserWith(string ...$permissions): User
{
    seedRolesAndPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo(array_merge(['admin.access'], $permissions));

    return $user;
}

function validBackup(): Backup
{
    $backup = Backup::factory()->create([
        'disk' => 'backups',
        'path' => 'archives/'.Str::uuid().'/backup.sql.gz',
    ]);

    Storage::disk('backups')->put((string) $backup->path, 'artifact');

    return $backup;
}

test('a guest cannot enter the backup area', function (): void {
    $this->get(route('backups.index'))->assertRedirect(route('login'));
});

test('a user without admin access is denied', function (): void {
    seedRolesAndPermissions();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('backups.index'))->assertForbidden();
});

test('view requires backups.view', function (): void {
    $without = backupUserWith();
    $this->actingAs($without)->get(route('backups.index'))->assertForbidden();

    $with = backupUserWith('backups.view');
    $this->actingAs($with)->get(route('backups.index'))->assertOk();
});

test('create requires backups.create', function (): void {
    Queue::fake();

    $without = backupUserWith('backups.view');
    $this->actingAs($without)->post(route('backups.store'), ['type' => 'database'])->assertForbidden();

    $with = backupUserWith('backups.create');
    $this->actingAs($with)->post(route('backups.store'), ['type' => 'database'])->assertRedirect();

    Queue::assertPushed(CreateBackupJob::class);
});

test('download requires its own permission and a real file', function (): void {
    $backup = validBackup();

    $without = backupUserWith('backups.view');
    $this->actingAs($without)->get(route('backups.download', $backup))->assertForbidden();

    $with = backupUserWith('backups.download');
    $this->actingAs($with)->get(route('backups.download', $backup))->assertOk();
});

test('upload requires backups.upload and does not grant restore', function (): void {
    $without = backupUserWith('backups.view');
    $this->actingAs($without)
        ->post(route('backups.upload'), ['file' => UploadedFile::fake()->create('backup.zip', 1)])
        ->assertForbidden();

    $with = backupUserWith('backups.upload');
    $this->actingAs($with)
        ->post(route('backups.upload'), ['file' => UploadedFile::fake()->create('backup.zip', 1)])
        ->assertSessionHasErrors('file');

    $backup = validBackup();
    $this->actingAs($with)->post(route('backups.restore.prepare', $backup))->assertForbidden();
});

test('restore preparation requires backups.restore', function (): void {
    Queue::fake();
    $backup = validBackup();

    $without = backupUserWith('backups.view');
    $this->actingAs($without)->post(route('backups.restore.prepare', $backup))->assertForbidden();

    $with = backupUserWith('backups.restore');
    $this->actingAs($with)->post(route('backups.restore.prepare', $backup))->assertRedirect();

    Queue::assertPushed(PrepareRestoreJob::class);
});

test('final restore requires a ready operation and the confirmation phrase', function (): void {
    Queue::fake();
    $backup = validBackup();
    $restore = BackupRestore::factory()->create([
        'backup_id' => $backup->getKey(),
        'status' => BackupRestoreStatus::Ready,
        'expires_at' => now()->addHour(),
    ]);

    $without = backupUserWith('backups.view');
    $this->actingAs($without)
        ->post(route('backups.restore', $backup), [
            'restore_id' => $restore->getKey(),
            'confirmation' => 'PULIHKAN DATA',
        ])
        ->assertForbidden();

    $with = backupUserWith('backups.restore');
    $this->actingAs($with)
        ->post(route('backups.restore', $backup), [
            'restore_id' => $restore->getKey(),
            'confirmation' => 'salah',
        ])
        ->assertSessionHasErrors('confirmation');

    $this->actingAs($with)
        ->post(route('backups.restore', $backup), [
            'restore_id' => $restore->getKey(),
            'confirmation' => 'PULIHKAN DATA',
        ])
        ->assertRedirect();
});

test('delete and settings are separate permissions', function (): void {
    $backup = validBackup();

    $viewer = backupUserWith('backups.view');
    $this->actingAs($viewer)->delete(route('backups.destroy', $backup))->assertForbidden();
    $this->actingAs($viewer)->get(route('backups.settings.edit'))->assertForbidden();

    $deleter = backupUserWith('backups.delete');
    $this->actingAs($deleter)->delete(route('backups.destroy', $backup))->assertRedirect();

    $settings = backupUserWith('backups.settings');
    $this->actingAs($settings)->get(route('backups.settings.edit'))->assertOk();
});
