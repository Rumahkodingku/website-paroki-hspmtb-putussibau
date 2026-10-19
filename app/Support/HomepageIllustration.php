<?php

namespace App\Support;

/**
 * The homepage's fallback illustrations.
 *
 * These are licensed stock photographs, never parish photographs, and they are
 * only used when a card has no real media. The whole feature is off unless
 * HOMEPAGE_ILLUSTRATIONS is true, because production must show the parish's own
 * uploads (AGENTS.md, DESIGN.md).
 *
 * Selection is deterministic: the same seed always resolves to the same image,
 * so a card does not change its picture between requests and two cards with
 * different ids do not necessarily repeat.
 *
 * @see docs/design-plans/beranda-ilustrasi-gambar-internet.md
 * @see docs/DECISIONS.md D-71
 */
final class HomepageIllustration
{
    /**
     * The three responsive shapes the homepage asks for.
     *
     * @var array<string, string>
     */
    private const SIZES = [
        'card' => '(min-width: 768px) 33vw, (min-width: 640px) 50vw, 100vw',
        'thumb' => '64px',
        // Masonry strip (D-75): two columns on a phone, three from md, with a
        // 12px gutter. The tile is therefore a little narrower than the old
        // grid's 33vw/50vw, which was sized for uniform columns.
        'gallery' => '(min-width: 768px) 31vw, 46vw',
    ];

    public static function enabled(): bool
    {
        return (bool) config('homepage.illustrations.enabled', false);
    }

    /**
     * One illustration for a seed, or null when the feature is off.
     *
     * @return array{src: string, srcSet: string, sizes: string, alt: string, width: int, height: int}|null
     */
    public static function for(string $seed, string $variant = 'card'): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        /** @var array<string, array{alt?: string, width?: int, height?: int, sources?: array<int|string, string>}> $images */
        $images = (array) config('homepage.illustrations.images', []);

        if ($images === []) {
            return null;
        }

        $keys = array_keys($images);
        $key = $keys[crc32($seed) % count($keys)];

        return self::shape($images[$key], $variant);
    }

    /**
     * Every configured illustration, in configuration order.
     *
     * Used for the gallery strip, which needs several distinct pictures rather
     * than the one a seed resolves to.
     *
     * @return list<array{id: string, src: string, srcSet: string, sizes: string, alt: string, width: int, height: int}>
     */
    public static function all(string $variant = 'card'): array
    {
        if (! self::enabled()) {
            return [];
        }

        /** @var array<string, array{alt?: string, width?: int, height?: int, sources?: array<int|string, string>}> $images */
        $images = (array) config('homepage.illustrations.images', []);

        $result = [];

        foreach ($images as $key => $image) {
            $shaped = self::shape($image, $variant);

            if ($shaped !== null) {
                $result[] = ['id' => (string) $key, ...$shaped];
            }
        }

        return $result;
    }

    /**
     * @param  array{alt?: string, width?: int, height?: int, sources?: array<int|string, string>}  $image
     * @return array{src: string, srcSet: string, sizes: string, alt: string, width: int, height: int}|null
     */
    private static function shape(array $image, string $variant): ?array
    {
        $sources = (array) ($image['sources'] ?? []);

        if ($sources === []) {
            return null;
        }

        // Ascending width so the srcSet is predictable and the largest source
        // is the plain src fallback.
        ksort($sources, SORT_NUMERIC);

        $candidates = [];
        $largest = null;

        foreach ($sources as $width => $path) {
            $url = '/'.ltrim((string) $path, '/');
            $candidates[] = $url.' '.$width.'w';
            $largest = $url;
        }

        return [
            'src' => (string) $largest,
            'srcSet' => implode(', ', $candidates),
            'sizes' => self::SIZES[$variant] ?? self::SIZES['card'],
            'alt' => (string) config('homepage.illustrations.alt_prefix', '').(string) ($image['alt'] ?? ''),
            'width' => (int) ($image['width'] ?? 1200),
            'height' => (int) ($image['height'] ?? 800),
        ];
    }
}
