<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Final archives live on a private disk. The temporary workspace is always
    | local so a dump or a staging extraction never has to be uploaded halfway
    | through. Neither path is ever exposed to the browser.
    |
    */

    'disk' => env('BACKUPS_DISK', 'backups'),

    'archive_path' => 'archives',

    'upload_path' => 'uploads',

    'temp_path' => storage_path('app/private/backup-temp'),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | A dedicated connection keeps retry_after above the longest job timeout.
    | The default database connection retries after 90 seconds, which would let
    | a second worker pick up a running dump.
    |
    */

    'queue_connection' => env('BACKUPS_QUEUE_CONNECTION', 'backups'),

    'queue' => env('BACKUPS_QUEUE', 'backups'),

    /*
    |--------------------------------------------------------------------------
    | MySQL binaries
    |--------------------------------------------------------------------------
    |
    | Paths come from configuration, never from a request. Password is passed
    | through the process environment, not through argv.
    |
    */

    'mysqldump_path' => env('BACKUPS_MYSQLDUMP_PATH', 'mysqldump'),

    'restore_test_database' => env('BACKUP_RESTORE_TEST_DATABASE', 'website_paroki_hspmtb_restore_test'),

    'mysql_path' => env('BACKUPS_MYSQL_PATH', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Format
    |--------------------------------------------------------------------------
    */

    'format' => 'hspmtb-backup',

    'format_version' => '1',

    'app_version' => env('APP_VERSION'),

    'app_commit' => env('APP_COMMIT'),

    /*
    |--------------------------------------------------------------------------
    | Timezones offered by the settings form
    |--------------------------------------------------------------------------
    */

    'timezones' => [
        'Asia/Pontianak',
        'Asia/Jakarta',
        'Asia/Makassar',
        'Asia/Jayapura',
        'UTC',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hard limits
    |--------------------------------------------------------------------------
    |
    | These are the technical ceiling. Admin-editable values in backup_settings
    | may be lower, never higher.
    |
    */

    'limits' => [
        'max_upload_megabytes' => 256,
        'max_expanded_megabytes' => 4096,
        'max_files' => 10000,
        'max_compression_ratio' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeouts
    |--------------------------------------------------------------------------
    */

    'timeouts' => [
        'dump_seconds' => 3600,
        'restore_seconds' => 7200,
        'lock_seconds' => 7200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Readiness heartbeats
    |--------------------------------------------------------------------------
    |
    | A stale value means "not detected", never "assume active".
    |
    */

    'heartbeat' => [
        'scheduler_ttl_seconds' => 300,
        'worker_ttl_seconds' => 900,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables excluded from a database dump
    |--------------------------------------------------------------------------
    |
    | Sessions, cache, queue and backup metadata are operational state, not the
    | application's content. Restoring them would delete current sessions and
    | the backup history that makes recovery possible.
    |
    */

    'excluded_tables' => [
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'backups',
        'backup_restores',
        'backup_settings',
    ],

];
