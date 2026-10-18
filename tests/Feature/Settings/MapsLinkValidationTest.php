<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| The Google Maps link in site settings
|--------------------------------------------------------------------------
|
| There is one admin field for the map, `maps_link`, holding a link copied from
| Google Maps' Share button. The embed iframe is derived from it at read time
| (App\Support\MapsUrl), so these tests are about what the write path accepts
| and stores: a Google Maps link, a pasted iframe, or nothing.
|
| Refusals are asserted against the stored column as well as the session, because
| a redirect that looks clean while the payload is in the database is the
| failure this file exists to catch.
|
| @see docs/DECISIONS.md D-67
|
*/

beforeEach(function () {
    Cache::forget(config('site-settings.cache.key'));
});

function storedSetting(string $key): ?string
{
    return SiteSetting::query()->where('key', $key)->value('value');
}

/**
 * @param  array<string, mixed>  $settings
 * @return TestResponse<Response>
 */
function saveSettings(array $settings)
{
    return test()->actingAs(superAdmin())
        ->from(route('settings.edit'))
        ->put(route('settings.update'), ['settings' => $settings]);
}

test('a Google Maps share link is stored as typed', function () {
    $url = 'https://www.google.com/maps/place/Paroki+HSPMTB/@-0.0293,111.7686,17z';

    saveSettings(['maps_link' => $url])->assertSessionHasNoErrors();

    expect(storedSetting('maps_link'))->toBe($url);
});

test('a q-style Google Maps link is accepted', function () {
    $url = 'https://www.google.com/maps?q=Paroki+Putussibau';

    saveSettings(['maps_link' => $url])->assertSessionHasNoErrors();

    expect(storedSetting('maps_link'))->toBe($url);
});

test('an iframe is reduced to its src before it is stored', function () {
    $src = 'https://www.google.com/maps/place/Paroki/@-0.0293,111.7686,17z';
    $iframe = '<iframe src="'.$src.'" width="600" height="450" allowfullscreen loading="lazy"></iframe>';

    saveSettings(['maps_link' => $iframe])->assertSessionHasNoErrors();

    $stored = storedSetting('maps_link');

    expect($stored)->toBe($src)
        ->and($stored)->not->toContain('<iframe');
});

test('an existing embed URL is accepted and stored as typed', function () {
    $url = 'https://www.google.com/maps/embed?pb=!1m18!2d111.7';

    saveSettings(['maps_link' => $url])->assertSessionHasNoErrors();

    expect(storedSetting('maps_link'))->toBe($url);
});

test('a short share link is refused with a message about the long link', function () {
    saveSettings(['maps_link' => 'https://maps.app.goo.gl/abc123'])
        ->assertSessionHasErrors([
            'settings.maps_link' => 'Link Google Maps yang dipendekkan tidak memuat lokasi. Buka Google Maps, tekan Bagikan, lalu salin link panjangnya.',
        ]);

    expect(storedSetting('maps_link'))->toBeNull();
});

test('a non-Google host is refused', function () {
    saveSettings(['maps_link' => 'https://maps.example.test/paroki'])
        ->assertSessionHasErrors('settings.maps_link');

    expect(storedSetting('maps_link'))->toBeNull();
});

test('a host that merely contains google.com is refused', function () {
    saveSettings(['maps_link' => 'https://www.google.com.evil.test/maps?q=x'])
        ->assertSessionHasErrors('settings.maps_link');

    expect(storedSetting('maps_link'))->toBeNull();
});

test('javascript and credential URLs are refused', function () {
    foreach ([
        'javascript:alert(1)',
        'https://user:pass@www.google.com/maps?q=x',
    ] as $value) {
        saveSettings(['maps_link' => $value])
            ->assertSessionHasErrors('settings.maps_link');
    }

    expect(storedSetting('maps_link'))->toBeNull();
});

test('a Google search URL is refused', function () {
    saveSettings(['maps_link' => 'https://www.google.com/search?q=paroki'])
        ->assertSessionHasErrors('settings.maps_link');

    expect(storedSetting('maps_link'))->toBeNull();
});

test('clearing the link stores null', function () {
    saveSettings(['maps_link' => 'https://www.google.com/maps?q=Paroki'])
        ->assertSessionHasNoErrors();

    saveSettings(['maps_link' => ''])->assertSessionHasNoErrors();

    expect(storedSetting('maps_link'))->toBeNull();
});
