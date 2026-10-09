<?php

use App\Support\PageChunk;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

/*
|--------------------------------------------------------------------------
| Architecture
|--------------------------------------------------------------------------
|
| Phase 01 section 33 P13. Most of what the roadmap forbids is written down in
| a document, and a document does not stop anybody. These turn the prohibitions
| that Pest's arch plugin can actually express into rules that fail a build.
|
| Every assertion here names the document it enforces, so a change that
| contradicts one of them is visibly a change and not an accident.
|
| Each one was checked by breaking the rule on purpose and confirming the test
| goes red, because an architecture test that cannot fail is a comment with a
| syntax error. That exercise also found two of them weaker than they look: the
| arch plugin resolves imports rather than call sites, so a sanitization rule
| warns that a dependency disappeared rather than proving it is still invoked.
| Those two say so in their own docblock and name the behavioural test that
| carries the real guarantee.
|
| Two rules are not arch assertions and say so, because the plugin cannot
| express them: a namespace that does not exist has no class list to assert on,
| and the plugin rejects a path target outright. They are written as ordinary
| assertions rather than bent into an arch rule that would only look like the
| thing it replaces.
|
| What is deliberately absent: `users` has no `role` column. That is a schema
| question and it is already asserted against the database by
| RbacFoundationTest. Arch reflects over code and cannot see a column, so
| repeating it here would add a test that proves nothing the other one has not.
|
| @see docs/DECISIONS.md D-27
|
*/

/*
|--------------------------------------------------------------------------
| No repository layer
|--------------------------------------------------------------------------
*/

test('there is no repository layer', function () {
    // Roadmap section 5.2: "Tidak perlu: Repository layer". Eloquent is the
    // persistence layer (ARCHITECTURE.md Part A section 6).
    expect(File::isDirectory(app_path('Repositories')))->toBeFalse()
        ->and(File::isDirectory(app_path('Domain')))->toBeFalse()
        ->and(class_exists('App\Repositories\BaseRepository'))->toBeFalse()
        // A bind() in a provider would reintroduce the layer without a folder.
        ->and(class_exists('App\Providers\RepositoryServiceProvider'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Services do not read the HTTP request
|--------------------------------------------------------------------------
*/

test('services do not depend on the http layer', function () {
    // ARCHITECTURE.md Part A section 6: "Services hold reusable logic or
    // integrations; they do not read the HTTP request."
    expect('App\Services')->not->toUse([
        'Illuminate\Http\Request',
        'Symfony\Component\HttpFoundation\Request',
    ]);
});

test('services do not depend on controllers', function () {
    // The same rule in the direction it actually causes harm: a service that
    // calls a controller has become a controller with extra steps.
    expect('App\Http\Controllers')->not->toBeUsedIn('App\Services');
});

/*
|--------------------------------------------------------------------------
| The prohibitions roadmap section 10.7 lists
|--------------------------------------------------------------------------
*/

test('there is no role or permission management controller', function () {
    // Roadmap section 10.7 forbids these four by name for Phase 01.
    $controllers = array_map(
        fn (string $path): string => Str::studly(str_replace(
            ['/', '.php'],
            ['\\', ''],
            Str::after($path, 'app/Http/Controllers/'),
        )),
        File::allFiles(app_path('Http/Controllers')),
    );

    /*
     * Compared as a set rather than with not->toContain($a, $b): negated
     * toContain with several needles asserts the array does not contain them
     * all, so a single violation still passes. That is a test that looks like
     * it is guarding something and is not.
     */
    $forbidden = [
        'RoleManagementController',
        'PermissionManagementController',
        'RoleManagementPage',
        'PermissionManagementPage',
    ];

    expect(array_values(array_intersect($forbidden, $controllers)))->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Model conventions
|--------------------------------------------------------------------------
*/

test('every model declares its fillable columns explicitly', function () {
    // ARCHITECTURE.md Part A section 6: "Always declare $fillable explicitly."
    //
    // This is an attribute, not a trait, and D-13 already depends on it:
    // is_active is absent from User's list on purpose so mass assignment cannot
    // set it.
    expect('App\Models')->toHaveAttribute('Illuminate\Database\Eloquent\Attributes\Fillable');
});

test('the user model gets its roles from spatie', function () {
    // PRD AUTH-R3 and section 10.2. The absence of a users.role column is
    // checked against the schema elsewhere; this pins the mechanism.
    expect('App\Models\User')->toUseTrait('Spatie\Permission\Traits\HasRoles');
});

test('models do not bypass eloquent with the query builder facade', function () {
    // Eloquent is the persistence layer. A DB:: call inside a model is the
    // first step toward a model that is only a bag of static helpers.
    expect('App\Models')->not->toUse(['Illuminate\Support\Facades\DB']);
});

/*
|--------------------------------------------------------------------------
| Frontend dependency direction
|--------------------------------------------------------------------------
|
| ARCHITECTURE.md Part B section 4 draws one arrow:
|
|     pages -> features -> components / hooks / lib / types
|
| and adds that `features/a` must not reach into `features/b`, and that
| `components/ui` and `components/shared` must not import from `features/*` or
| `pages/*`. Nothing was enforcing any of it, so the whole structure rested on
| nobody happening to write the wrong import.
|
| Import specifiers are resolved to a path under resources/js before the rules
| are applied, and that is the part worth reading. Matching on the `@/` alias
| alone would pass a file that reaches a forbidden directory with
| `../../features/...`, which is both a real bypass and something a codebase
| that mixes both styles will eventually produce.
|
| What is deliberately NOT a rule, because the document does not say it:
| `components/ui` importing from `hooks/`. sonner.tsx does exactly that, and
| AGENTS.md records it as a deliberate deviation. The documented rule forbids
| features and pages, so ui stays free of hooks and the deviation stays legal
| instead of being quietly grandfathered in here.
|
| @see docs/ARCHITECTURE.md Part B section 4
|
*/

/**
 * Every import in a frontend file, resolved to a path under resources/js.
 *
 * Bare package specifiers (react, lucide-react, @inertiajs/react) resolve to
 * an empty string so callers can ignore them. Relative specifiers are
 * normalised against the importing file's directory, collapsing `.` and `..`,
 * so the result is comparable no matter how the import was written.
 *
 * @return list<string>
 */
function frontendImports(SplFileInfo $file): array
{
    preg_match_all(
        '/(?:from\s*|import\s*\(\s*)[\'"]([^\'"]+)[\'"]/',
        (string) $file->getContents(),
        $matches,
    );

    /** @var list<string> $specifiers */
    $specifiers = $matches[1];

    // Relative to resources/js, not to the project root: an `@/lib/utils` target
    // comes back as `lib/utils`, and a `./auth` target has to come back the same
    // way or every rule below compares two different shapes.
    /** @var list<string> $segments */
    $segments = explode('/', (string) Str::beforeLast(
        Str::after($file->getRealPath(), base_path().'/resources/js/'),
        '/',
    ));

    return array_values(array_filter(array_map(
        static function (string $specifier) use ($segments): string {
            if (Str::startsWith($specifier, '@/')) {
                return Str::after($specifier, '@/');
            }

            if (! Str::startsWith($specifier, '.')) {
                return '';
            }

            $resolved = $segments;

            foreach (explode('/', $specifier) as $segment) {
                if ($segment === '.') {
                    continue;
                }

                if ($segment === '..') {
                    array_pop($resolved);

                    continue;
                }

                $resolved[] = $segment;
            }

            return implode('/', $resolved);
        },
        $specifiers,
    )));
}

/**
 * Files under a frontend directory that import a forbidden target.
 *
 * The generated Wayfinder output is skipped: it is written by a plugin, its
 * import style is not ours to change, and it is not covered by tsc.
 *
 * @param  Closure(string): bool  $forbidden  Receives a path under resources/js.
 * @return list<string>
 */
function frontendViolations(string $directory, Closure $forbidden): array
{
    $generated = ['actions/', 'routes/', 'wayfinder/'];
    $violations = [];

    foreach (File::allFiles(resource_path('js/'.$directory)) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        $relative = Str::after($file->getRealPath(), base_path().'/');

        if (Str::startsWith(Str::after($relative, 'resources/js/'), $generated)) {
            continue;
        }

        foreach (frontendImports($file) as $target) {
            if ($forbidden($target)) {
                $violations[] = "{$relative} imports {$target}";
            }
        }
    }

    return $violations;
}

/**
 * The feature a frontend file belongs to, as "audience/feature".
 *
 * Two segments, not one. features/ holds an audience directory and then a
 * feature directory, so taking the first segment would make every admin feature
 * look like one feature called "admin" — and then features/admin/akun importing
 * its own settings types would be reported as a cross-feature import.
 *
 * Returned as "audience/feature" so the message reads as a path rather than as
 * an audience and a name that happened to be concatenated.
 */
function frontendFeature(string $relative): string
{
    // First two segments, taken with array_slice rather than Str::before twice:
    // the audience is the first segment and the feature is the second, and
    // everything after them is the file's own path inside the feature.
    $segments = explode('/', Str::after($relative, 'features/'));

    return implode('/', array_slice($segments, 0, 2));
}

test('frontend components do not import pages', function () {
    // Part B section 4: components sit below pages in the arrow. A component
    // that imports a page is how a page stops being thin and starts being a
    // component with a hard dependency on its own caller.
    expect(frontendViolations('components', fn (string $t): bool => Str::startsWith($t, 'pages/')))
        ->toBe([]);
});

test('shadcn primitives do not import features or pages', function () {
    // Part B section 4 names components/ui explicitly. ui/ is regenerated by
    // `shadcn add`, so anything it knows about our features is knowledge that a
    // regeneration would delete.
    expect(frontendViolations('components/ui', fn (string $t): bool => Str::startsWith($t, 'features/') || Str::startsWith($t, 'pages/')))
        ->toBe([]);
});

test('layouts do not import pages or features', function () {
    // The arrow does not mention layouts/, so this rule is derived rather than
    // quoted: app.tsx wires a layout to a page by component name, which means a
    // layout importing that page is a cycle. It is stated here so it is a
    // decision instead of an accident waiting to happen.
    expect(frontendViolations('layouts', fn (string $t): bool => Str::startsWith($t, 'pages/') || Str::startsWith($t, 'features/')))
        ->toBe([]);
});

test('a feature does not import another feature', function () {
    // Part B section 4: "features/a MUST NOT import from features/b. If both
    // need it, move it to components/shared, hooks, lib, or types." Checked per
    // importing feature, so the message names which way the dependency points.
    $violations = [];

    foreach (File::allFiles(resource_path('js/features')) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        $relative = Str::after($file->getRealPath(), base_path().'/');
        $owner = frontendFeature($relative);

        foreach (frontendImports($file) as $target) {
            if (! Str::startsWith($target, 'features/')) {
                continue;
            }

            // Str::after, not Str::before: the target starts with 'features/',
            // so looking for the text before it yields an empty string and every
            // intra-feature import looks like a violation.
            if (frontendFeature('features/'.Str::after($target, 'features/')) !== $owner) {
                $violations[] = "features/{$owner} imports {$target}";
            }
        }
    }

    expect($violations)->toBe([]);
});

test('hooks and lib import nothing above them', function () {
    // Part B section 4 puts hooks and lib on the same level as components, at
    // the bottom of the arrow. They are the leaves everything else is allowed to
    // depend on, so an import going the other way would make the cycle
    // unrepresentable.
    $reachesUp = fn (string $t): bool => Str::startsWith($t, 'pages/')
        || Str::startsWith($t, 'features/')
        || Str::startsWith($t, 'components/')
        || Str::startsWith($t, 'layouts/');

    expect(frontendViolations('hooks', $reachesUp))
        ->toBe([])
        ->and(frontendViolations('lib', $reachesUp))->toBe([]);
});

test('no frontend directory imports one that does not exist', function () {
    // Not a documented rule. Catches the failure mode this change actually had:
    // a path that resolves for the bundler and for tsc, but whose directory is
    // not one of the ten under resources/js. Cheap, and it failed for real once.
    // No 'pages': pages moved into features/<audience>/<feature>/pages/, and a
    // resources/js/pages/ directory reappearing is exactly the mistake this test
    // is here to catch.
    $known = ['actions', 'components', 'features', 'hooks', 'layouts', 'lib', 'routes', 'types', 'wayfinder'];
    $generated = ['actions', 'routes', 'wayfinder'];
    $stray = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        $relative = Str::after($file->getRealPath(), base_path().'/');
        $below = Str::after($relative, 'resources/js/');

        // A file sitting directly in resources/js belongs to no directory, so
        // there is nothing for it to be inside of.
        if (! str_contains($below, '/')) {
            continue;
        }

        $directory = Str::before($below, '/');

        if (! in_array($directory, $known, true)) {
            $stray[] = $relative;

            continue;
        }

        // Wayfinder output is written by a plugin: its import style is not ours
        // to change and it is not covered by tsc.
        if (in_array($directory, $generated, true)) {
            continue;
        }

        foreach (frontendImports($file) as $target) {
            // A bare `@/types` names a sibling directory, not a missing one.
            if ($target === '' || ! str_contains($target, '/')) {
                continue;
            }

            if (! in_array(Str::before($target, '/'), $known, true)) {
                $stray[] = "{$relative} imports {$target}";
            }
        }
    }

    expect($stray)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Where Inertia pages live
|--------------------------------------------------------------------------
|
| Pages moved out of a single resources/js/pages/ directory into
| features/<audience>/<feature>/pages/. Three things know about that layout and
| all three have to agree:
|
|   resources/js/app.tsx       the resolver, via import.meta.glob
|   config/inertia.php         Inertia's own view finder, used both when a page
|                              is rendered and by assertInertia()->component()
|   app/Support/PageChunk.php  the Blade template, preloading the page chunk
|
| The middle one is the surprising one. Nothing in the application asked for it:
| it is Inertia's config, it takes literal directories rather than wildcards, and
| getting it wrong does not fail at boot. It fails as one
| "Inertia page component file [x] does not exist" per test that renders a
| page — twelve of them, each naming a page rather than the configuration that
| was wrong.
|
| @see docs/DECISIONS.md D-30
|
*/

test('the page finder finds every page file in the application', function () {
    // Inertia's own FileViewFinder was replaced with App\Support\PageFinder,
    // because it can only concatenate a directory with a name. That replacement
    // is what twelve tests were failing against while it was missing, and the
    // failure named a page rather than the configuration at fault.
    //
    // This walks the filesystem rather than a list of names, so a page added
    // without a matching config entry cannot slip past.
    $pages = PageChunk::all();

    expect($pages)->not->toBeEmpty();

    foreach ($pages as $name => $path) {
        expect(app('inertia.view-finder')->find($name))->toBe($path);
    }
});

test('the page finder rejects a name that resolves to nothing', function () {
    // The exception is what Inertia turns into a ComponentNotFoundException on
    // render, and into "component file does not exist" inside an assertion.
    expect(fn (): string => app('inertia.view-finder')->find('admin/akun/halaman-yang-tidak-ada'))
        ->toThrow(InvalidArgumentException::class);
});

test('no two features claim the same page name', function () {
    // The frontend derives a page's name from its path, so two features holding a
    // page that derived the same name would overwrite each other in that index.
    // Silently: no build error, no failed request, and the feature that lost
    // would render the other feature's page.
    //
    // Structurally impossible today, because the name carries the audience and
    // the feature. It is still a test rather than a note, because that stops
    // being true the moment a third level appears — features/admin/settings/akun
    // would put two features one level apart and names would stop being unique by
    // construction.
    $owners = [];

    foreach (File::allFiles(resource_path('js/features')) as $file) {
        if ($file->getExtension() !== 'tsx') {
            continue;
        }

        $path = Str::after($file->getRealPath(), base_path().'/');

        if (preg_match(
            '#^resources/js/features/(?<owner>[^/]+/[^/]+)/pages/(?<rest>.+)\.tsx$#',
            $path,
            $matches,
        ) !== 1) {
            continue;
        }

        // index.tsx names the feature itself, so four features each holding one
        // is four distinct page names and not a collision. Reading the file name
        // directly would report all four as "index" and fail on the layout that
        // is working.
        $name = $matches['rest'] === 'index'
            ? $matches['owner']
            : $matches['owner'].'/'.$matches['rest'];

        $owners[$name][] = $matches['owner'];
    }

    expect(array_filter($owners, fn (array $list): bool => count($list) > 1))->toBe([]);
});

test('the inertia page finder and the blade lookup find the same files', function () {
    // config('inertia.pages.paths') and PageChunk::source() answer the same
    // question by different means: one concatenates a literal directory with a
    // component name, the other rebuilds the path from the name's own segments.
    // They can drift apart without either being wrong on its own, and then Inertia
    // renders a page whose chunk was never preloaded.
    //
    // The path is rebuilt here rather than taken from PageChunk, on purpose: a
    // comparison that reads its expectation from one of the two sides under test
    // only proves the other side agrees with itself.
    foreach (array_keys(PageChunk::all()) as $name) {
        $segments = explode('/', $name);
        $below = implode('/', array_slice($segments, 2));

        // Only what sits below the pages/ directory: the configured path already
        // ends there, so prefixing the audience and feature again would look one
        // level too deep.
        $file = ($below === '' ? 'index' : $below).'.tsx';

        $found = false;

        foreach ((array) config('inertia.pages.paths') as $directory) {
            if (is_file(rtrim((string) $directory, '/')."/{$file}")) {
                $found = true;

                break;
            }
        }

        expect($found)->toBeTrue();
    }
});

/*
|--------------------------------------------------------------------------
| The security rules that must not be quietly dropped
|--------------------------------------------------------------------------
*/

test('html is sanitized in the request layer', function () {
    // PRD XC-S1 and D-14: rich text is sanitized server-side. D-25 put that in
    // UpdateSettingsRequest::prepareForValidation().
    //
    // What this actually proves is narrow and worth being precise about: arch
    // resolves *imports*, not call sites. So this fails if the request layer
    // stops knowing about the sanitizer, and it would still pass if somebody
    // deleted the clean() call and left the import behind. The behavioural
    // guarantee lives in SiteSettingsTest, which posts a script element and
    // asserts on the column; this is the early warning, not the proof.
    expect('App\Http\Requests')->toUse(['App\Services\HtmlSanitizer']);
});

test('only the error page renders sanitized html on the frontend', function () {
    // The frontend counterpart of the rule above: React must not inject raw
    // HTML anywhere else, because the only thing making it safe is the
    // sanitizer having run on the way into storage.
    $offenders = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        if (str_contains($file->getFilename(), 'rich-text.tsx')) {
            continue;
        }

        if (str_contains($file->getContents(), 'dangerouslySetInnerHTML')) {
            $offenders[] = Str::after($file->getRealPath(), base_path().'/');
        }
    }

    expect($offenders)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| The public shell's three token contracts
|--------------------------------------------------------------------------
|
| Phase 03 completed the public navigation, which turned three rules that had
| only ever been written in prose into things a machine could check. Each of the
| three was, at some point during this phase, actually broken:
|
|   - the navbar had href: '/' and the footer had two href: '#'
|   - two of the footer groups reached for a brand colour by hex value
|   - the Seo component and app.blade.php kept their head keys in step by hand,
|     which is exactly the kind of agreement that survives review and dies in a
|     merge
|
| None of them is a style preference. A dead href is navigation to nowhere; an
| arbitrary hex cannot be re-themed and DESIGN.md forbids it outright; a
| duplicated meta tag is read by a crawler as two descriptions.
|
*/

test('navigation never hard-codes an application URL', function () {
    // AGENTS.md: "Never hard-code app URLs." Wayfinder exists so that changing a
    // route prefix is one edit rather than a search, and a literal string opts
    // out of that silently - the link keeps working, which is why nobody notices
    // until the prefix moves.
    //
    // Both shapes are refused, and a regex rather than a list of literals
    // because a list was the wrong shape twice: it caught href="/" but let
    // href="/beranda" through, which is the same mistake with a different
    // word. Any href whose value is a string starting with a slash is a
    // hard-coded application URL.
    //
    // A generated href cannot match: href={jadwalMisa()} and href={link.href}
    // are calls or variables, and the pattern needs a quote right after the
    // equals sign (with an optional brace for href={"..."}).
    //
    // A consequence worth knowing: the pattern also matches inside a comment,
    // so a component cannot explain what it is not allowed to write by writing
    // it. That is mildly annoying and mildly protective.
    //
    // Scanned across every frontend file rather than a named pair, because the
    // rule is not about the navbar and the footer specifically - it is about any
    // navigation in the app - and because naming two files made this test break
    // the day the parish navbar moved under layouts/public/components.
    $offenders = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        $relative = Str::after($file->getRealPath(), base_path().'/');

        // Generated output is not ours to restyle.
        if (Str::startsWith(Str::after($relative, 'resources/js/'), [
            'actions/',
            'routes/',
            'wayfinder/',
        ])) {
            continue;
        }

        $contents = (string) $file->getContents();

        if (preg_match('/\bhref\s*(?:=|:)\s*\{?\s*[\'"]\//', $contents, $matches) === 1) {
            $offenders[] = "{$relative}: {$matches[0]}";
        }
    }

    expect($offenders)->toBe([]);
});

test('no component reaches for a brand colour by hex value', function () {
    // DESIGN.md, "Tailwind Rules": avoid arbitrary values such as bg-[#AB020E]
    // in application components, and keep the brand tokens in the global theme.
    //
    // The pattern matched is a Tailwind arbitrary value, not a hex in prose:
    // [ #AB020E ] is the only way a colour reaches the DOM this way, so this
    // cannot fire on a comment or on a documentation string the way a bare
    // /#[0-9A-F]{6}/ would.
    $offenders = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        // Generated and restyled on purpose; the shadcn primitives carry their
        // own values and AGENTS.md forbids editing them.
        if (Str::startsWith(Str::after($file->getRealPath(), base_path().'/resources/js/'), [
            'actions/',
            'routes/',
            'wayfinder/',
            'components/ui/',
        ])) {
            continue;
        }

        if (preg_match('/\[#[0-9A-Fa-f]{3,8}\]/', (string) $file->getContents()) === 1) {
            $offenders[] = Str::after($file->getRealPath(), base_path().'/');
        }
    }

    expect($offenders)->toBe([]);
});

test('the seo head keys match the ones the blade template renders', function () {
    // Inertia manages the document head by the data-inertia attribute and matches
    // a server tag to a client tag by its value. That is the only mechanism
    // joining the two layers, and it is load-bearing: SSR is off (D-26), so the
    // blade output is what a crawler and a WhatsApp unfurl actually read.
    //
    // A key present in one file and not the other does not error. It produces a
    // page where the client tag sits *next to* the server tag instead of
    // replacing it - two meta descriptions, two og:title - and nothing in a
    // browser shows it. Phase 03 added robots and og:locale to both sides in one
    // change; this is what keeps the next person from adding them to one.
    $blade = (string) File::get(resource_path('views/app.blade.php'));
    $component = (string) File::get(resource_path('js/components/seo.tsx'));

    preg_match_all('/data-inertia="([^"]+)"/', $blade, $bladeMatches);
    preg_match_all('/head-key="([^"]+)"/', $component, $componentMatches);

    $inBlade = array_values(array_unique($bladeMatches[1]));
    $inComponent = array_values(array_unique($componentMatches[1]));

    // Only keys the two are meant to share. og:title, og:description and
    // canonical are also conditional in blade, and canonical is emitted as a
    // <link> in both, so a key can legitimately appear in one file and not the
    // other without being a bug. What must never happen is a client key with no
    // server counterpart, because that tag can never replace anything.
    expect(array_diff($inComponent, $inBlade))->toBe([]);
});
