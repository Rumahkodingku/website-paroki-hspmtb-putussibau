<?php

use App\Support\OfficeHours;

/*
|--------------------------------------------------------------------------
| OfficeHours
|--------------------------------------------------------------------------
|
| The one setting that holds a whole week. These are unit tests: the parsing
| and the display grouping are the behaviour, and neither touches the database.
|
| @see docs/DECISIONS.md D-68
|
*/

test('an empty week has seven closed days in week order', function () {
    $week = OfficeHours::emptyWeek();

    expect(array_keys($week))->toBe(array_keys(OfficeHours::DAYS))
        ->and($week)->toHaveCount(7)
        ->and($week['monday'])->toBe(['is_open' => false, 'open_time' => null, 'close_time' => null]);
});

test('normalize fills a full week from a partial array', function () {
    $week = OfficeHours::normalize([
        'monday' => ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'],
    ]);

    expect($week)->not->toBeNull()
        ->and(array_keys((array) $week))->toBe(array_keys(OfficeHours::DAYS))
        ->and($week['monday']['is_open'])->toBeTrue()
        ->and($week['tuesday']['is_open'])->toBeFalse();
});

test('normalize treats an open day with no times as closed', function () {
    $week = OfficeHours::normalize([
        'monday' => ['is_open' => true, 'open_time' => null, 'close_time' => null],
    ]);

    expect($week['monday']['is_open'])->toBeFalse();
});

test('normalize refuses a legacy string so it is not mistaken for a week', function () {
    expect(OfficeHours::normalize('Senin–Jumat, 08.00–15.00'))->toBeNull();
});

test('consecutive open days with the same times become one range', function () {
    $week = OfficeHours::emptyWeek();

    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
        $week[$day] = ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'];
    }

    expect(OfficeHours::format($week))->toBe(
        "Senin–Jumat: 08.00–15.00 WIB\nSabtu: Tutup\nMinggu: Tutup"
    );
});

test('a different time on one day breaks the range', function () {
    $week = OfficeHours::emptyWeek();

    foreach (['monday', 'tuesday'] as $day) {
        $week[$day] = ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'];
    }
    $week['wednesday'] = ['is_open' => true, 'open_time' => '09:00', 'close_time' => '12:00'];

    expect(OfficeHours::format($week))->toBe(
        "Senin–Selasa: 08.00–15.00 WIB\nRabu: 09.00–12.00 WIB\nKamis: Tutup\nJumat: Tutup\nSabtu: Tutup\nMinggu: Tutup"
    );
});

test('a week with every day closed has nothing to show', function () {
    expect(OfficeHours::format(OfficeHours::emptyWeek()))->toBeNull();
});

test('a legacy string is returned unchanged', function () {
    expect(OfficeHours::format('  Senin–Jumat, 08.00–15.00  '))
        ->toBe('Senin–Jumat, 08.00–15.00');
});

test('nothing configured has nothing to show', function () {
    expect(OfficeHours::format(null))->toBeNull()
        ->and(OfficeHours::format(''))->toBeNull();
});

test('closed days keep their retained times when normalized', function () {
    $week = OfficeHours::normalize([
        'saturday' => ['is_open' => false, 'open_time' => '08:00', 'close_time' => '12:00'],
    ]);

    expect($week['saturday'])->toBe([
        'is_open' => false,
        'open_time' => '08:00',
        'close_time' => '12:00',
    ]);
});
