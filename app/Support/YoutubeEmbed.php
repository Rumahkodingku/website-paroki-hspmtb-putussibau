<?php

namespace App\Support;

/**
 * Reads a YouTube video id out of a pasted URL.
 *
 * Only YouTube is supported because the public profile embeds the video in an
 * iframe. Anything else is refused by App\Rules\YoutubeUrl instead of being
 * stored as a link that can never be played.
 *
 * The host allowlist is exact, not a substring check: `youtube.com.evil.test`
 * contains `youtube.com` and must not pass.
 */
final class YoutubeEmbed
{
    /**
     * Video id, or null when the URL is not a supported YouTube link.
     */
    public static function id(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $parts = parse_url(trim($url));

        if ($parts === false || ! isset($parts['host'], $parts['path'])) {
            return null;
        }

        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = trim($parts['path'], '/');

        if ($host === 'youtu.be') {
            return self::validId($path);
        }

        if (! in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) {
            return null;
        }

        if ($path === 'watch') {
            parse_str($parts['query'] ?? '', $query);

            return self::validId(is_string($query['v'] ?? null) ? $query['v'] : null);
        }

        foreach (['embed', 'shorts', 'live'] as $prefix) {
            if (str_starts_with($path, $prefix.'/')) {
                return self::validId(substr($path, strlen($prefix) + 1));
            }
        }

        return null;
    }

    /**
     * The privacy-friendly embed URL for a stored video URL, or null.
     */
    public static function url(?string $url): ?string
    {
        $id = self::id($url);

        return $id === null ? null : "https://www.youtube-nocookie.com/embed/{$id}";
    }

    private static function validId(?string $id): ?string
    {
        if ($id === null || preg_match('/^[A-Za-z0-9_-]{11}$/', $id) !== 1) {
            return null;
        }

        return $id;
    }
}
