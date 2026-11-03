<?php

namespace App\Support;

/**
 * The one place a document title is formatted.
 *
 * Blade and React both render a title, and a title that reads differently in
 * the first response than after hydration is the exact failure the server-side
 * resolver exists to prevent. The rule is therefore tiny and identical on both
 * sides: page title, then the parish name, separated by a pipe.
 */
final class SeoTitle
{
    public static function format(string $title, ?string $siteName): string
    {
        $title = trim($title);
        $siteName = $siteName === null ? null : trim($siteName);

        if ($title === '') {
            return $siteName ?? '';
        }

        if ($siteName === null || $siteName === '') {
            return $title;
        }

        if ($title === $siteName || str_contains($title, $siteName)) {
            return $title;
        }

        return $title.' | '.$siteName;
    }
}
