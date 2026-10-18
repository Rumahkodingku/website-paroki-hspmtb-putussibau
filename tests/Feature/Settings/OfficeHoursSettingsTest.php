<?php

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| Weekly office hours in site settings
|--------------------------------------------------------------------------
|
| office_hours is the one structured setting: a whole week submitted as an
| array and stored as JSON. These tests cover what the write path accepts,
| what it refuses, and that it never loses a legacy free-text value.
|
| @see docs/DECISIONS.md D-68
|
*/

beforeEach(function () {
    Cache::forget(config('site-settings.cache.key'));
});

/**
 * A full week, Monday to Friday open and the weekend closed.
 *
 * @return array<string, array{is_open: bool, open_time: string|null, close_time: string|null}>
 */
function validWeek(): array
{
    $week = [];

    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
        $week[$day] = ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'];
    }

    foreach (['saturday', 'sunday'] as $day) {
        $week[$day] = ['is_open' => false, 'open_time' => null, 'close_time' => null];
    }

    return $week;
}

/**
 * @param  array<string, mixed>  $settings
 * @return TestResponse<Response>
 */
function putSettings(array $settings)
{
    return test()->actingAs(superAdmin())
        ->from(route('settings.edit'))
        ->put(route('settings.update'), ['settings' => $settings]);
}

/**
 * @return array<string, mixed>|null
 */
function storedOfficeHours(): ?array
{
    $raw = SiteSetting::query()->where('key', 'office_hours')->value('value');

    return is_string($raw) ? json_decode($raw, true) : null;
}

test('a full week is stored as JSON and reads back as an array', function () {
    putSettings(['office_hours' => validWeek()])->assertSessionHasNoErrors();

    $stored = storedOfficeHours();

    expect($stored)->toBeArray()
        ->and($stored['monday'])->toBe(['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'])
        ->and($stored['sunday'])->toBe(['is_open' => false, 'open_time' => null, 'close_time' => null]);

    expect(app(SiteSettingsService::class)->get('office_hours'))
        ->toBe($stored);
});

test('a closed day may be submitted without times', function () {
    putSettings(['office_hours' => validWeek()])->assertSessionHasNoErrors();

    expect(storedOfficeHours()['saturday']['is_open'])->toBeFalse();
});

test('a closed day keeps the times it was submitted with', function () {
    $week = validWeek();
    $week['saturday'] = ['is_open' => false, 'open_time' => '08:00', 'close_time' => '12:00'];

    putSettings(['office_hours' => $week])->assertSessionHasNoErrors();

    expect(storedOfficeHours()['saturday'])
        ->toBe(['is_open' => false, 'open_time' => '08:00', 'close_time' => '12:00']);
});

test('an open day without an opening time is refused', function () {
    $week = validWeek();
    $week['monday']['open_time'] = null;

    putSettings(['office_hours' => $week])
        ->assertSessionHasErrors('settings.office_hours.monday.open_time');

    expect(SiteSetting::query()->where('key', 'office_hours')->exists())->toBeFalse();
});

test('an open day without a closing time is refused', function () {
    $week = validWeek();
    $week['monday']['close_time'] = null;

    putSettings(['office_hours' => $week])
        ->assertSessionHasErrors('settings.office_hours.monday.close_time');
});

test('a closing time at or before the opening time is refused', function () {
    $week = validWeek();
    $week['monday'] = ['is_open' => true, 'open_time' => '15:00', 'close_time' => '15:00'];

    putSettings(['office_hours' => $week])
        ->assertSessionHasErrors('settings.office_hours.monday.close_time');

    $week['monday'] = ['is_open' => true, 'open_time' => '15:00', 'close_time' => '08:00'];

    putSettings(['office_hours' => $week])
        ->assertSessionHasErrors('settings.office_hours.monday.close_time');
});

test('a week missing a day is refused', function () {
    $week = validWeek();
    unset($week['wednesday']);

    putSettings(['office_hours' => $week])
        ->assertSessionHasErrors('settings.office_hours');
});

test('a non-boolean status is refused', function () {
    $week = validWeek();
    $week['monday']['is_open'] = 'yes';

    putSettings(['office_hours' => $week])
        ->assertSessionHasErrors('settings.office_hours.monday.is_open');
});

test('a time that is not HH:mm is refused', function () {
    $week = validWeek();
    $week['monday']['open_time'] = '8 pagi';

    putSettings(['office_hours' => $week])
        ->assertSessionHasErrors('settings.office_hours.monday.open_time');
});

test('a legacy free-text value is accepted and preserved', function () {
    putSettings(['office_hours' => 'Senin–Jumat, 08.00–15.00'])->assertSessionHasNoErrors();

    expect(SiteSetting::query()->where('key', 'office_hours')->value('value'))
        ->toBe('Senin–Jumat, 08.00–15.00')
        ->and(app(SiteSettingsService::class)->get('office_hours'))
        ->toBe('Senin–Jumat, 08.00–15.00');
});

test('saving the schedule does not touch the other contact settings', function () {
    putSettings([
        'contact_phone' => '0812 3456 789',
        'office_hours' => validWeek(),
    ])->assertSessionHasNoErrors();

    expect(app(SiteSettingsService::class)->get('contact_phone'))->toBe('0812 3456 789')
        ->and(storedOfficeHours()['friday']['is_open'])->toBeTrue();
});

test('the settings cache is dropped after saving the schedule', function () {
    app(SiteSettingsService::class)->get('office_hours');

    expect(Cache::has(config('site-settings.cache.key')))->toBeTrue();

    putSettings(['office_hours' => validWeek()])->assertSessionHasNoErrors();

    expect(Cache::has(config('site-settings.cache.key')))->toBeFalse();
});

test('a guest cannot save the schedule', function () {
    $this->put(route('settings.update'), ['settings' => ['office_hours' => validWeek()]])
        ->assertRedirect(route('login'));

    expect(SiteSetting::query()->where('key', 'office_hours')->exists())->toBeFalse();
});
