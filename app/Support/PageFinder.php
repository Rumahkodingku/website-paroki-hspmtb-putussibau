<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Locates the file behind an Inertia component name.
 *
 * Replaces the `inertia.view-finder` binding Laravel's own FileViewFinder
 * provides, because that one can only concatenate. It looks for
 * `<configured path>/<component name>.<extension>` and nothing else, which only
 * works while a page name *is* a path below a directory. Two features of the
 * current layout defeat it:
 *
 *   - the audience and feature are in the name but not in the file's path below
 *     `pages/`, so the concatenation lands one level too deep;
 *   - `index.tsx` stands for the feature itself, and there is no
 *     `admin/pengaturan.tsx` for it to find.
 *
 * Both are handled by PageChunk, which is the single place the rule lives.
 *
 * Why this matters beyond the test suite: Inertia calls the finder in
 * ResponseFactory::findComponentOrFail() whenever a page is rendered, and in
 * AssertableInertia::component() for every assertion. Getting the finder wrong
 * does not fail at boot — it turns into a 500 on the first request, or into
 * "Inertia page component file [x] does not exist" from each test that renders
 * a page, which names a page rather than the configuration that is wrong.
 *
 * Only find() is implemented. That is the whole of the interface the callers
 * actually use; nothing renders the returned string as a Blade view, since an
 * Inertia page is a JavaScript module and not a template.
 *
 * @see docs/DECISIONS.md D-30
 */
final class PageFinder
{
    /**
     * @throws InvalidArgumentException when no file matches
     */
    public function find(string $name): string
    {
        $path = PageChunk::source($name);

        if ($path === null) {
            throw new InvalidArgumentException(
                "Inertia page component [{$name}] not found. Pages live at ".
                'features/<audience>/<feature>/pages/, where index.tsx stands '.
                'for the feature itself.'
            );
        }

        return $path;
    }
}
