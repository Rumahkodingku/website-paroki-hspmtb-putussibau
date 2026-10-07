<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| Application configuration (Phase 01 Workstream 01 + 02)
|--------------------------------------------------------------------------
|
| Berkas ini mengunci fondasi yang diminta Phase 01 §7 (Workstream 02) dan
| Test Matrix T01/T02/T04/T05/T06:
|
|   - T01 application boot
|   - T02 database connection
|   - T04 app.timezone = UTC            (PRD D-01)
|   - T05 app.display_timezone = WIB    (PRD D-01)
|   - T06 app.locale = id               (PRD NFR-I18N)
|
| Nilai dikunci di phpunit.xml agar tidak bergantung pada .env lokal.
|
*/

test('application boots', function () {
    expect(app())->not->toBeNull()
        ->and(app()->isBooted())->toBeTrue();
});

test('database connection is available', function () {
    $connection = config('database.default');

    expect($connection)->toBeString()
        ->and(DB::connection()->getPdo())->toBeInstanceOf(PDO::class);
});

test('tests never run against the development database', function () {
    // phpunit.xml menunjuk database test secara hard-code. RefreshDatabase akan
    // migrate:fresh, jadi database ini tidak boleh sama dengan database dev.
    //
    // env('DB_DATABASE') tidak bisa dipakai untuk ini: phpunit.xml menimpa
    // nilai tersebut, sehingga ia selalu mengembalikan nama database test.
    $connection = config('database.default');
    $database = config("database.connections.{$connection}.database");

    expect($database)->toEndWith('_test');
});

test('datetimes are stored in UTC', function () {
    // D-01: jangan pernah diubah ke Asia/Pontianak.
    expect(config('app.timezone'))->toBe('UTC');
});

test('Asia/Pontianak is the display timezone', function () {
    // D-01: seluruh datetime ke frontend sebagai ISO 8601 dalam zona ini.
    expect(config('app.display_timezone'))->toBe('Asia/Pontianak')
        ->and(config('app.display_timezone'))->not->toBe(config('app.timezone'));
});

test('the application uses the Indonesian locale', function () {
    expect(config('app.locale'))->toBe('id')
        ->and(config('app.fallback_locale'))->toBe('id')
        ->and(config('app.faker_locale'))->toBe('id_ID')
        ->and(app()->getLocale())->toBe('id');
});

test('validation messages are rendered in Indonesian', function () {
    $required = Validator::make(
        ['email' => ''],
        ['email' => ['required']],
    )->errors()->first('email');

    $format = Validator::make(
        ['email' => 'bukan-email'],
        ['email' => ['email']],
    )->errors()->first('email');

    expect($required)->toBe('Email wajib diisi.')
        ->and($format)->toBe('Email harus berupa alamat email yang valid.');
});

test('validation attributes use Indonesian field names', function () {
    $message = Validator::make(
        ['password_confirmation' => ''],
        ['password' => ['required']],
    )->errors()->first('password');

    // :attribute untuk "password" harus menjadi istilah Indonesia, bukan
    // "Password" hasil auto-generation Laravel.
    expect($message)->toBe('Kata sandi wajib diisi.');
});

test('authentication failure message is translated', function () {
    expect(__('auth.failed'))->toBe('Email atau kata sandi tidak cocok dengan data kami.')
        ->and(__('auth.throttle'))->toContain(':seconds');
});

test('password reset messages are translated', function () {
    expect(__('passwords.sent'))->toBe('Tautan pengaturan ulang kata sandi telah dikirim ke email Anda.')
        ->and(__('passwords.reset'))->toBe('Kata sandi Anda berhasil diperbarui.')
        ->and(__('passwords.user'))->toBe('Kami tidak menemukan akun dengan alamat email tersebut.');
});

test('pagination labels are translated', function () {
    expect(__('pagination.previous'))->toContain('Sebelumnya')
        ->and(__('pagination.next'))->toContain('Berikutnya');
});

test('no validation rule falls back to an untranslated key', function () {
    // fallback_locale juga bernilai "id", sehingga tidak ada jaring pengaman ke
    // Bahasa Inggris. Aturan yang hilang akan mengembalikan string kunci apa
    // adanya - test inilah yang menjaganya. Lihat docs/DECISIONS.md D-02.
    $translator = app('translator');
    $english = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');

    $rules = array_keys(array_diff_key($english, ['custom' => null, 'attributes' => null]));

    $untranslated = [];
    foreach ($rules as $rule) {
        $translated = $translator->get("validation.{$rule}");

        // MessageTooShortException / string kunci berarti aturan hilang.
        if (is_string($translated) && str_contains($translated, "validation.{$rule}")) {
            $untranslated[] = $rule;
        }
    }

    expect($untranslated)->toBe([]);
});

test('every English validation rule exists in the Indonesian file', function () {
    $english = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
    $indonesian = require base_path('lang/id/validation.php');

    // Jaga agar kunci yang hilang terdeteksi: hanya "custom" dan "attributes"
    // yang boleh tidak ada di berkas Indonesia (keduanya bertipe array).
    expect(array_keys($indonesian))
        ->toEqual(array_keys($english));
});
