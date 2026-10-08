<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Finds the file behind an Inertia page name.
 *
 * A page's name encodes its path inside a feature:
 *
 *   'admin/akun/profile'  →  features/admin/akun/pages/profile.tsx
 *   'admin/dashboard'     →  features/admin/dashboard/pages/index.tsx
 *   'public/auth/login'   →  features/public/auth/pages/login.tsx
 *   'public/beranda'      →  features/public/beranda/pages/index.tsx
 *
 * The first two segments are the audience and the feature, and whatever is left
 * is the path below `pages/`. An empty remainder means `index.tsx`, which is
 * what lets a single-page feature drop the redundant segment its URL carries
 * without renaming the page.
 *
 * Because the name fully determines the path, this needs no wildcard search at
 * all. An earlier version globbed across the audience and feature segments on
 * the assumption that the name was still a path — which stopped being true the
 * moment the file stopped repeating the URL, and quietly returned the first
 * index.tsx it found for any unresolved two-segment name.
 *
 * The wildcard is spelled out rather than written inline for the third time
 * this repository has been bitten by it: an asterisk followed by a slash inside
 * a PHP docblock closes the comment.
 *
 * The Blade template needs this to preload the page chunk. It used to be a
 * string interpolation, `"resources/js/pages/{$page['component']}.tsx"`, which
 * only worked because pages lived in one directory. Vite marks a dynamic entry
 * as a preload only when it is passed to @vite(), so this is load-bearing: drop
 * it and the first paint of every page waits an extra round trip for its own
 * JavaScript.
 *
 * JavaScript derives the same mapping in the other direction in
 * resources/js/app.tsx, turning each globbed path into a name. Both sides read
 * the filesystem rather than sharing a table, so there is nothing to keep in
 * step — at the cost of implementing the index rule twice, which is why
 * PageChunkTest asserts the two agree on every page in the application.
 *
 * @see docs/DECISIONS.md D-30
 */
final class PageChunk
{
    /**
     * The source path of a page chunk, relative to the project root.
     *
     * Returns null when nothing matches, which is a real answer rather than an
     * error case: a component name that no longer resolves must not be turned
     * into a malformed @vite() call that throws somewhere else.
     */
    public static function source(string $component): ?string
    {
        // The component name reaches us from a controller, so it is not a
        // trusted path. Rejecting '..' and a leading slash keeps a name from
        // walking out of resources/js, which is the only place it may read.
        if ($component === '' || str_contains($component, '..') || str_starts_with($component, '/')) {
            return null;
        }

        $segments = explode('/', $component);

        if (count($segments) < 2) {
            return null;
        }

        [$audience, $feature] = $segments;

        $below = implode('/', array_slice($segments, 2));
        $file = resource_path("js/features/{$audience}/{$feature}/pages/".($below === '' ? 'index' : $below).'.tsx');

        if (! is_file($file)) {
            return null;
        }

        // resource_path() hands back an absolute path; @vite() wants the path
        // relative to the project root, and returning an absolute one makes it
        // look for an asset outside the build.
        return Str::after($file, base_path().'/');
    }

    /**
     * Every page source path, keyed by Inertia page name.
     *
     * Used by the architecture tests rather than by a request: it is what makes
     * "no two features claim the same page name" checkable, and it is the list
     * the frontend and backend mappings are compared against.
     *
     * Not walked by hand, because PHP's glob has no recursive wildcard: a
     * single `*` does not cross a directory separator, so a pattern with one
     * level of it would only ever find pages one directory down and would
     * quietly miss `pages/admin/akun/profile.tsx`.
     *
     * @return array<string, string> page name => source path
     */
    public static function all(): array
    {
        $pages = [];

        foreach (File::allFiles(resource_path('js/features')) as $file) {
            if ($file->getExtension() !== 'tsx') {
                continue;
            }

            $relative = Str::after($file->getRealPath(), base_path().'/');

            if (preg_match(
                '#^resources/js/features/(?<audience>[^/]+)/(?<feature>[^/]+)/pages/(?<rest>.+)\.tsx$#',
                $relative,
                $matches,
            ) !== 1) {
                continue;
            }

            $rest = $matches['rest'];

            $pages[$rest === 'index'
                ? $matches['audience'].'/'.$matches['feature']
                : $matches['audience'].'/'.$matches['feature'].'/'.$rest] = $relative;
        }

        return $pages;
    }
}
