<?php

use App\Providers\AppServiceProvider;
use Database\Seeders\CommunityCategorySeeder;
use Database\Seeders\DocumentCategorySeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PostCategorySeeder;
use Database\Seeders\ServiceCategorySeeder;

/*
|--------------------------------------------------------------------------
| Installation wizard
|--------------------------------------------------------------------------
|
| Everything the installer needs to decide whether it may run, where it keeps
| its state, and which seeders a production install is allowed to execute.
|
| The status lives in a file rather than a table because no table can exist
| before the first migration. It holds no secret, so an operator can read it
| over SSH when an installation fails.
|
| @see docs/DECISIONS.md D-90 … D-95
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Activation
    |--------------------------------------------------------------------------
    |
    | Off by default: an installer reachable on a running site is a security
    | hole, and the deployment that enables it is the one that knows the
    | database is still empty.
    |
    */

    'enabled' => (bool) env('INSTALLER_ENABLED', false),

    'status_path' => storage_path('app/private/installation/status.json'),

    'lock_key' => 'installation.run',

    'lock_seconds' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | The project pins MySQL 8 (PRD 3.1). MariaDB has a connection in
    | config/database.php but no test suite behind it, so the installer does
    | not offer it.
    |
    */

    'database' => [
        'supported_drivers' => ['mysql'],
        'probe_connection' => 'installer-probe',
    ],

    /*
    |--------------------------------------------------------------------------
    | Requirements
    |--------------------------------------------------------------------------
    |
    | Extensions come from composer.json (framework) and the code that runs on
    | every install: gd for the media pipeline, zip for documents and backups,
    | pdo_mysql for the database. curl is only needed by the optional
    | uncompromised() password check, so it is a warning.
    |
    | Paths are config so the checker can be tested against fixtures instead of
    | the real application directories.
    |
    */

    'requirements' => [
        'minimum_php' => '8.3.0',
        'extensions' => [
            'ctype',
            'filter',
            'hash',
            'mbstring',
            'openssl',
            'session',
            'tokenizer',
            'pdo',
            'pdo_mysql',
            'gd',
            'zip',
            'fileinfo',
        ],
        'warning_extensions' => ['curl'],
        'minimum_execution_time' => 120,
        'paths' => [
            'storage' => storage_path(),
            'cache' => base_path('bootstrap/cache'),
            'manifest' => public_path('build/manifest.json'),
            'hot' => public_path('hot'),
            'storage_link' => public_path('storage'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Production seeders
    |--------------------------------------------------------------------------
    |
    | Explicit allow-list. DatabaseSeeder is deliberately absent: it also runs
    | SiteSettingsSeeder, ServiceSeeder, SuperAdminSeeder and the demo content,
    | none of which belong in an installation driven by the wizard.
    |
    */

    'seeders' => [
        'permissions' => [
            PermissionSeeder::class,
        ],
        'categories' => [
            PostCategorySeeder::class,
            EventCategorySeeder::class,
            ServiceCategorySeeder::class,
            DocumentCategorySeeder::class,
            CommunityCategorySeeder::class,
        ],
    ],

    'role' => AppServiceProvider::SUPER_ADMIN,

];
