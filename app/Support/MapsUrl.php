<?php

namespace App\Support;

use DOMDocument;
use DOMElement;

/**
 * One place that turns a pasted Google Maps link into a directions link and an
 * embed iframe URL.
 *
 * The admin pastes one thing — a link copied from Google Maps' Share button.
 * From that single value this class produces both the "Petunjuk Arah" link and
 * the iframe `src`, so the parish never has to hunt down an embed code. PRD
 * KON-02 wanted the embed derived rather than hand-pasted; here it is derived
 * from the share link instead of from an embed URL.
 *
 * The embed is built with Google's public `?output=embed` endpoint. It is not
 * the documented Embed API and needs no key, but it is the same endpoint the
 * consumer Share button has produced for years. A share link always carries
 * either a `q` parameter, a `/place/<name>` segment, or an `@lat,lng` pair, and
 * one of those becomes the query; the zoom is read from the link and falls back
 * to a building-level default. A short `maps.app.goo.gl` link carries none of
 * that and is refused rather than resolved over the network.
 *
 * This class is shared by the settings FormRequest (write path), the public
 * `contact` shared prop (read path), and the `GoogleMapsLink` rule, so the three
 * cannot disagree about what "valid" means.
 *
 * @see docs/PRD.md KON-02, KON-07, XC-S2
 * @see docs/DECISIONS.md D-67
 */
final class MapsUrl
{
    /**
     * The zoom used when the pasted link does not carry one. Sixteen is close
     * enough to show a building and its street without hiding either.
     */
    private const DEFAULT_ZOOM = 16;

    private const MIN_ZOOM = 1;

    private const MAX_ZOOM = 21;

    /**
     * Pull a URL out of the value the admin pasted.
     *
     * A value starting with an `<iframe>` is parsed as HTML and the first
     * iframe's `src` is returned, so an admin who still has the old embed code
     * to hand is not stuck. Anything else is treated as a URL. An empty value or
     * an iframe with no `src` returns null.
     */
    public static function extract(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^<iframe\b/i', $value)) {
            return $value;
        }

        return self::iframeSrc($value);
    }

    /**
     * A short Google share link, which cannot become an embed on its own.
     *
     * Kept separate from isGoogleMapsLink() so the rule can say why the value
     * was refused instead of showing the generic host message.
     */
    public static function isShortLink(?string $value): bool
    {
        $url = self::extract($value);

        if ($url === null) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return false;
        }

        /** @var list<string> $hosts */
        $hosts = array_map('strtolower', (array) config('site-settings.maps.short_hosts', []));

        return in_array(strtolower($parts['host']), $hosts, true);
    }

    /**
     * Whether the value is a Google Maps link this application accepts.
     *
     * The scheme must be https, the URL must not carry credentials, and the host
     * and path must match config('site-settings.maps.link_hosts'). A share link
     * always lands under /maps on www.google.com, or directly on
     * maps.google.com.
     */
    public static function isGoogleMapsLink(?string $value): bool
    {
        $url = self::extract($value);

        if ($url === null) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (strtolower($parts['scheme']) !== 'https') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $host = strtolower($parts['host']);
        $path = strtolower($parts['path'] ?? '');

        /** @var array<string, list<string>> $hosts */
        $hosts = (array) config('site-settings.maps.link_hosts', []);

        // Read the map and index it rather than using dot notation: a host name
        // contains dots, so config('...link_hosts.www.google.com') would look
        // for a nested key that does not exist.
        $prefixes = $hosts[$host] ?? null;

        if (! is_array($prefixes)) {
            return false;
        }

        foreach ($prefixes as $prefix) {
            if ($prefix === '/' || str_starts_with($path, strtolower($prefix))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The stored link, or null when it is not a Google Maps link.
     *
     * Directions are not an embed: the value is returned as pasted so the
     * visitor leaves for the canonical Google Maps page rather than a stripped
     * frame.
     */
    public static function directions(?string $value): ?string
    {
        $url = self::extract($value);

        if ($url === null) {
            return null;
        }

        return self::isGoogleMapsLink($url) ? $url : null;
    }

    /**
     * An embeddable Google Maps URL, or null.
     *
     * An already-embedded URL (the old /maps/embed?pb=... form, or a pasted
     * iframe) is passed through unchanged, so an existing value keeps working.
     * Anything else is rebuilt from the link's query and zoom.
     */
    public static function embed(?string $value): ?string
    {
        $url = self::extract($value);

        if ($url === null || ! self::isGoogleMapsLink($url)) {
            return null;
        }

        if (self::isEmbedUrl($url)) {
            return $url;
        }

        $query = self::placeQuery($url);

        if ($query === null) {
            return null;
        }

        return sprintf(
            'https://www.google.com/maps?q=%s&z=%d&output=embed',
            rawurlencode($query),
            self::zoom($url),
        );
    }

    /**
     * Whether the URL is already the embed form of a Google Maps URL.
     */
    private static function isEmbedUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['path'])) {
            return false;
        }

        $path = strtolower($parts['path']);

        return $path === '/maps/embed' || str_starts_with($path, '/maps/embed/');
    }

    /**
     * The place a link points at, as a query string for the embed endpoint.
     *
     * In order of how specific they are: a `q` parameter, a `/place/<name>` or
     * `/search/<name>` segment, then a bare `@lat,lng` pair. Null means the link
     * carries none of them, which is the case for a short share link.
     */
    private static function placeQuery(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            foreach (['q', 'query'] as $key) {
                $value = $query[$key] ?? null;

                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        $path = $parts['path'] ?? '';

        foreach (['/place/', '/search/'] as $marker) {
            $position = stripos($path, $marker);

            if ($position === false) {
                continue;
            }

            $rest = substr($path, $position + strlen($marker));
            $name = trim(urldecode(explode('/', $rest)[0]));

            if ($name !== '') {
                return $name;
            }
        }

        if (preg_match('#/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)#', $path, $matches) === 1) {
            return $matches[1].','.$matches[2];
        }

        return null;
    }

    /**
     * The zoom carried by the link, clamped, or the default.
     */
    private static function zoom(string $url): int
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return self::DEFAULT_ZOOM;
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            if (isset($query['z']) && is_numeric($query['z'])) {
                return self::clampZoom((int) round((float) $query['z']));
            }
        }

        $path = $parts['path'] ?? '';

        if (preg_match('#/@-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?,(\d+(?:\.\d+)?)z#', $path, $matches) === 1) {
            return self::clampZoom((int) round((float) $matches[1]));
        }

        return self::DEFAULT_ZOOM;
    }

    private static function clampZoom(int $zoom): int
    {
        return max(self::MIN_ZOOM, min(self::MAX_ZOOM, $zoom));
    }

    /**
     * The first iframe's src, or null when the markup has none.
     */
    private static function iframeSrc(string $html): ?string
    {
        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML($html, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $iframe = $document->getElementsByTagName('iframe')->item(0);

        if (! $iframe instanceof DOMElement) {
            return null;
        }

        $src = trim($iframe->getAttribute('src'));

        return $src === '' ? null : $src;
    }
}
