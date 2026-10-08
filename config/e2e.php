<?php

return [

    /*
    |--------------------------------------------------------------------------
    | End-to-End Test Environment
    |--------------------------------------------------------------------------
    |
    | Settings for the Playwright suite in tests/e2e. They live in a config file
    | rather than in the command or the Playwright config so that both sides
    | read one source of truth: AppE2ePrepare creates the account and
    | `npm run e2e` logs into it.
    |
    | The credentials below are throwaway fixtures on a reserved TLD. They are
    | not parish data and must never be reused anywhere real. Override them with
    | E2E_ADMIN_* if you need to, for example to test the password confirmation
    | screen.
    |
    | @see docs/DECISIONS.md D-29
    */

    /*
    | The only database Playwright may ever migrate. AppE2ePrepare refuses to
    | run against anything else, because migrate:fresh is destructive and a
    | mistyped environment variable should not be able to wipe a developer's
    | local data. Keep this in step with phpunit.xml's DB_DATABASE.
    */
    'database' => env('E2E_DATABASE', 'website_paroki_hspmtb_test'),

    'admin' => [
        'name' => env('E2E_ADMIN_NAME', 'Super Admin E2E'),

        'email' => env('E2E_ADMIN_EMAIL', 'e2e-admin@example.test'),

        'password' => env('E2E_ADMIN_PASSWORD', 'E2e-Admin-2026!'),
    ],

    /*
    | Written by AppE2ePrepare and read by the Playwright specs. The file exists
    | so the specs log into exactly the account that was just seeded: hard-coding
    | the credentials twice would let the two drift apart, and the suite would
    | then fail on a password mismatch while looking like a login bug.
    |
    | Git-ignored. It holds a throwaway password in plaintext.
    */
    'auth_file' => base_path('tests/e2e/.auth.json'),

];
