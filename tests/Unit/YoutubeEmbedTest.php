<?php

use App\Support\YoutubeEmbed;

it('reads the id from the supported YouTube URLs', function (string $url) {
    expect(YoutubeEmbed::id($url))->toBe('dQw4w9WgXcQ');
})->with([
    'watch' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'watch with extra query' => 'https://www.youtube.com/watch?t=42&v=dQw4w9WgXcQ',
    'short' => 'https://youtu.be/dQw4w9WgXcQ',
    'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'shorts' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'live' => 'https://www.youtube.com/live/dQw4w9WgXcQ',
    'mobile' => 'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
]);

it('refuses everything that is not a YouTube video', function (string $url) {
    expect(YoutubeEmbed::id($url))->toBeNull();
})->with([
    'not youtube' => 'https://example.test/watch?v=dQw4w9WgXcQ',
    'host prefix trick' => 'https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ',
    'wrong scheme' => 'ftp://youtu.be/dQw4w9WgXcQ',
    'missing id' => 'https://www.youtube.com/watch',
    'invalid id' => 'https://youtu.be/too-short',
    'channel page' => 'https://www.youtube.com/@paroki',
]);

it('builds a privacy-friendly embed URL', function () {
    expect(YoutubeEmbed::url('https://youtu.be/dQw4w9WgXcQ'))
        ->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and(YoutubeEmbed::url('https://example.test/video'))->toBeNull()
        ->and(YoutubeEmbed::url(null))->toBeNull();
});

it('builds the public URLs from an already extracted id', function () {
    expect(YoutubeEmbed::embedUrl('dQw4w9WgXcQ'))
        ->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and(YoutubeEmbed::watchUrl('dQw4w9WgXcQ'))
        ->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->and(YoutubeEmbed::thumbnailUrl('dQw4w9WgXcQ'))
        ->toBe('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
});

it('refuses to build a URL from an invalid id', function () {
    expect(YoutubeEmbed::embedUrl('too-short'))->toBeNull()
        ->and(YoutubeEmbed::watchUrl(null))->toBeNull()
        ->and(YoutubeEmbed::thumbnailUrl('dQw4w9WgXcQ<script>'))->toBeNull();
});
