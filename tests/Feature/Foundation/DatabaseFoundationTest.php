<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Database foundation (Phase 01 Workstream 03)
|--------------------------------------------------------------------------
|
| Mengunci skema fondasi dari Phase 01 §8 dan Test Matrix T03 / T07 / T08:
|
|   - T03 migration pass
|   - T07 user active default = true
|   - T08 users has no role column
|
| Plus the site_settings contract from PRD 9.2 and Phase 01 §8.2, and a
| guard that no fabricated parish or admin data can creep back in.
|
*/

test('every migration has run', function () {
    // Phase 01 §8.3 system tables.
    $systemTables = [
        'password_reset_tokens',
        'sessions',
        'jobs',
        'failed_jobs',
        'cache',
        'cache_locks',
    ];

    foreach ($systemTables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("tabel {$table} tidak ada");
    }

    // Phase 01 §3.1.C application foundation table.
    expect(Schema::hasTable('site_settings'))->toBeTrue();

    // Phase 01 §8.1 users columns.
    expect(Schema::hasColumn('users', 'is_active'))->toBeTrue();
});

test('new users are active by default', function () {
    // Insert straight through the query builder, deliberately omitting
    // is_active, so this proves the database default rather than a factory
    // default (Phase 01 §8.1 requires DEFAULT TRUE).
    DB::table('users')->insert([
        'name' => 'Tanpa Kolom',
        'email' => 'tanpa-kolom@example.test',
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('users')->where('email', 'tanpa-kolom@example.test')->value('is_active'))
        ->toBe(1);
});

test('users have no role column', function () {
    // Phase 01 §39 MUST NOT: users.role. Roles live in Spatie Permission.
    expect(Schema::hasColumn('users', 'role'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'role_id'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'roles'))->toBeFalse();
});

test('site settings have the documented columns', function () {
    // PRD 9.2 and Phase 01 §8.2. `key` and `group` are reserved words in
    // MySQL, so this also proves Laravel quotes them correctly.
    expect(Schema::hasColumns('site_settings', [
        'id',
        'key',
        'value',
        'group',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('site setting keys are unique', function () {
    DB::table('site_settings')->insert(['key' => 'parish_name', 'value' => 'A']);

    expect(fn () => DB::table('site_settings')->insert(['key' => 'parish_name', 'value' => 'B']))
        ->toThrow(QueryException::class);
});

test('site settings accept a null value and a null group', function () {
    DB::table('site_settings')->insert(['key' => 'kontak', 'value' => null, 'group' => null]);

    $row = DB::table('site_settings')->where('key', 'kontak')->first();

    expect($row->value)->toBeNull()
        ->and($row->group)->toBeNull();
});

test('site settings can be read and filtered by group', function () {
    // The query path the SiteSettingsService will use in P09. Exercised here so
    // the reserved `group` column is proven to work in a where clause.
    DB::table('site_settings')->insert([
        ['key' => 'parish_name', 'value' => 'Paroki', 'group' => 'identitas'],
        ['key' => 'contact_phone', 'value' => '[ISI: nomor sekretariat]', 'group' => 'kontak'],
    ]);

    expect(DB::table('site_settings')->where('group', 'kontak')->count())->toBe(1)
        ->and(DB::table('site_settings')->where('group', 'identitas')->value('key'))->toBe('parish_name');
});

test('the active scope excludes deactivated accounts', function () {
    User::factory()->count(2)->create();
    User::factory()->inactive()->create();

    expect(User::query()->count())->toBe(3)
        ->and(User::active()->count())->toBe(2);
});

test('is_active is cast to a boolean', function () {
    expect(superAdmin()->is_active)->toBeTrue()
        ->and(User::factory()->inactive()->create()->is_active)->toBeFalse();
});

test('the seeder creates no fabricated parish or admin data', function () {
    // AGENTS.md: never invent real parish data. The starter kit seeded a
    // "Test User", which must not come back.
    //
    // This asserts the absence of fabricated rows rather than an empty users
    // table, because P05 legitimately seeds a Super Admin when ADMIN_EMAIL is
    // configured. Asserting a row count would fail for the right reason but the
    // wrong one.
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'test@example.org')->exists())->toBeFalse()
        ->and(User::query()->where('name', 'Test User')->exists())->toBeFalse()
        ->and(DB::table('site_settings')->count())->toBe(0);
});
