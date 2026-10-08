<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin Bootstrap
    |--------------------------------------------------------------------------
    |
    | Credentials for the very first Super Admin account, created by
    | SuperAdminSeeder (Phase 01 Workstream 06). They live in a config file
    | rather than being read with env() inside the seeder, because env()
    | returns null once the configuration is cached and the seeder would then
    | silently skip creating the account.
    |
    | Leave admin.email or admin.password empty and the seeder does nothing,
    | so `migrate:fresh --seed` works before this is configured. Never commit
    | real values.
    |
    | @see docs/DECISIONS.md D-17
    */

    'name' => env('ADMIN_NAME'),

    'email' => env('ADMIN_EMAIL'),

    'password' => env('ADMIN_PASSWORD'),

];
