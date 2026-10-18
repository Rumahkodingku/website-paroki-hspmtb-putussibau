<?php

use App\Support\MapsUrl;

/*
|--------------------------------------------------------------------------
| MapsUrl
|--------------------------------------------------------------------------
|
| The admin pastes one Google Maps link and this class derives both the
| directions link and the embed iframe from it. These are unit tests: no
| database, no request, because the parsing is the whole behaviour.
|
| The embed uses Google's public `?output=embed` endpoint, which needs no key.
| The tests pin the query and zoom that come out of each link shape, so a
| change to the endpoint or the parser fails here rather than silently on the
| public page.
|
| @see docs/DECISIONS.md D-67
|
*/

test('a place link becomes a query embed at the link zoom', function () {
    expect(MapsUrl::embed('https://www.google.com/maps/place/Paroki+HSPMTB/@-0.0293,111.7686,17z'))
        ->toBe('https://www.google.com/maps?q=Paroki%20HSPMTB&z=17&output=embed');
});

test('a q link becomes a query embed at the default zoom', function () {
    expect(MapsUrl::embed('https://www.google.com/maps?q=Paroki+Putussibau'))
        ->toBe('https://www.google.com/maps?q=Paroki%20Putussibau&z=16&output=embed');
});

test('a bare coordinate link keeps its coordinates', function () {
    expect(MapsUrl::embed('https://www.google.com/maps/@-0.0293,111.7686,15z'))
        ->toBe('https://www.google.com/maps?q=-0.0293%2C111.7686&z=15&output=embed');
});

test('the maps.google.com host is accepted', function () {
    expect(MapsUrl::embed('https://maps.google.com/?q=-0.0293,111.7686'))
        ->toBe('https://www.google.com/maps?q=-0.0293%2C111.7686&z=16&output=embed');
});

test('an existing embed url is passed through unchanged', function () {
    $url = 'https://www.google.com/maps/embed?pb=!1m18!2d111.7';

    expect(MapsUrl::embed($url))->toBe($url);
});

test('an iframe is reduced to its src first', function () {
    expect(MapsUrl::embed('<iframe src="https://www.google.com/maps?q=Paroki"></iframe>'))
        ->toBe('https://www.google.com/maps?q=Paroki&z=16&output=embed');
});

test('the zoom is clamped to the map range', function () {
    expect(MapsUrl::embed('https://www.google.com/maps/@-0.02,111.7,99z'))
        ->toContain('&z=21&')
        ->and(MapsUrl::embed('https://www.google.com/maps?q=Paroki&z=0'))
        ->toContain('&z=1&');
});

test('the directions link is the pasted link', function () {
    $url = 'https://www.google.com/maps/place/Paroki/@-0.02,111.7,17z';

    expect(MapsUrl::directions($url))->toBe($url);
});

test('short links and non-google hosts are refused', function () {
    foreach ([
        'https://maps.app.goo.gl/abc123',
        'https://goo.gl/maps/abc123',
        'https://www.google.com.evil.test/maps?q=x',
        'https://maps.example.test/paroki',
        'https://www.google.com/search?q=paroki',
        'javascript:alert(1)',
        'https://user:pass@www.google.com/maps?q=x',
    ] as $url) {
        expect(MapsUrl::embed($url))->toBeNull()
            ->and(MapsUrl::directions($url))->toBeNull();
    }
});

test('short links are recognised as such', function () {
    expect(MapsUrl::isShortLink('https://maps.app.goo.gl/abc123'))->toBeTrue()
        ->and(MapsUrl::isShortLink('https://www.google.com/maps?q=x'))->toBeFalse();
});

test('a google maps link with no place has no embed but still has directions', function () {
    $url = 'https://www.google.com/maps';

    expect(MapsUrl::embed($url))->toBeNull()
        ->and(MapsUrl::directions($url))->toBe($url);
});
