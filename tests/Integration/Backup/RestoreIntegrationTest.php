<?php

use App\Services\Backup\DatabaseDumper;
use App\Services\Backup\DatabaseRestorer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Proves that the dump/restore pair works against a disposable database.
 *
 * This is deliberately outside RefreshDatabase: the dump is read by an external
 * mysqldump process, and the restore is performed by an external mysql process.
 * Neither can see an open test transaction.
 */
beforeEach(function (): void {
    $this->database = (string) config('backups.restore_test_database');
    $this->directory = sys_get_temp_dir().'/hspmtb-restore-'.Str::uuid();
    File::ensureDirectoryExists($this->directory);

    $base = config('database.connections.mysql');

    if (! is_array($base)) {
        $this->markTestSkipped('The mysql connection is not configured.');
    }

    config([
        'database.connections.mysql_restore_test' => array_merge($base, [
            'database' => $this->database,
        ]),
    ]);

    try {
        DB::connection('mysql')->statement('CREATE DATABASE IF NOT EXISTS `'.$this->database.'`');
        Artisan::call('migrate:fresh', [
            '--database' => 'mysql_restore_test',
            '--force' => true,
        ]);
    } catch (Throwable $exception) {
        $this->markTestSkipped('The disposable restore database is not available: '.$exception->getMessage());
    }
});

afterEach(function (): void {
    try {
        DB::purge('mysql_restore_test');
        DB::connection('mysql')->statement('DROP DATABASE IF EXISTS `'.$this->database.'`');
    } catch (Throwable) {
        // Cleanup failure must not hide the assertion result.
    }

    File::deleteDirectory($this->directory);
});

test('a dumped database can be restored into a disposable database', function (): void {
    $connection = DB::connection('mysql_restore_test');

    $connection->table('site_settings')->insert([
        'key' => 'integration_marker',
        'value' => 'sebelum-restore',
        'group' => 'identitas',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dump = $this->directory.'/database.sql';
    app(DatabaseDumper::class)->dump($dump, 'mysql_restore_test');

    expect(is_file($dump))->toBeTrue();

    $connection->table('site_settings')->where('key', 'integration_marker')->delete();
    expect($connection->table('site_settings')->where('key', 'integration_marker')->exists())->toBeFalse();

    app(DatabaseRestorer::class)->restore($dump, 'mysql_restore_test');
    DB::purge('mysql_restore_test');

    $value = DB::connection('mysql_restore_test')
        ->table('site_settings')
        ->where('key', 'integration_marker')
        ->value('value');

    expect($value)->toBe('sebelum-restore');
});
