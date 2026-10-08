<?php

/*
|--------------------------------------------------------------------------
| The continuous integration script
|--------------------------------------------------------------------------
|
| Phase 01 section 33 P13. T29 to T33 are all asserted by tools that already
| exist, and every one of them runs because composer ci:check names it.
|
| That is the fragile part, and it is fragile silently. Removing "npm run
| check" from the script does not break anything: the frontend still has zero
| lint errors, TypeScript still compiles, and the build still succeeds. The
| only evidence is a change in behaviour that never happens, so it is worth a
| test that costs nothing.
|
| It also fails loudly if someone adds a gate to the script and forgets to
| satisfy it, which is the failure mode this file is more likely to cause than
| to prevent.
|
| @see docs/DECISIONS.md D-27
|
*/

/**
 * A composer script flattened to one searchable string.
 *
 * Composer accepts either a string or an array of commands for a script, and
 * every script this file cares about is an array. Casting one to string throws
 * "Array to string conversion", and running substring assertions against the
 * array instead looks for exact element equality, so both forms have to be
 * normalised before anything below can read them.
 *
 * @return array<string, string>
 */
function composerScripts(): array
{
    /** @var array<string, mixed> $decoded */
    $decoded = json_decode((string) file_get_contents(base_path('composer.json')), true);

    $flattened = [];

    /** @var mixed $value */
    foreach ((array) data_get($decoded, 'scripts', []) as $name => $value) {
        $flattened[(string) $name] = is_array($value)
            ? implode(' ', array_map(strval(...), $value))
            : (string) $value;
    }

    return $flattened;
}

test('ci:check runs every gate the phase claims to have', function () {
    $scripts = composerScripts();

    // T29 TypeScript, T30 ESLint, T31 Pint, T32 Larastan, and the suite itself.
    expect($scripts['ci:check'] ?? '')->toContain('npm run check')
        ->toContain('npm run types:check')
        ->toContain('test');
});

test('the composer test script checks style and types before running the suite', function () {
    // Ordering is the point: a suite that runs first pays full cost on a commit
    // that Pint was going to reject in a second.
    $script = composerScripts()['test'] ?? '';

    // strpos() is int|false, and a missing step would compare false against
    // false. Filtering to the integers first turns a missing step into a failed
    // count instead of a comparison that quietly means nothing.
    $positions = array_values(array_filter(
        [strpos($script, 'lint:check'), strpos($script, 'artisan test')],
        is_int(...),
    ));

    // Comparing against a sorted copy rather than indexing: array_filter over a
    // two element list produces a list whose keys are optional, so $positions[0]
    // is "might not exist" even though the count assertion above guarantees it.
    // Asking whether the list is already ascending says the same thing and needs
    // no offsets.
    $ascending = $positions;
    sort($ascending);

    expect($positions)->toHaveCount(2)
        ->and($positions)->toBe($ascending);
});

test('lint:check does not modify files and lint does', function () {
    // Both are named by the gate CI runs. A check that rewrites files is a
    // format command wearing a check's name, and in CI it turns a green build
    // red for a reason nobody can see in the diff.
    $scripts = composerScripts();

    expect($scripts['lint:check'] ?? '')->toContain('pint')->toContain('--test')
        ->and($scripts['lint'] ?? '')->toContain('pint')
        ->and($scripts['lint'] ?? '')->not->toContain('--test')
        ->and($scripts['types:check'] ?? '')->toContain('analyse');
});

test('phpstan covers the application and the tests', function () {
    $config = (string) file_get_contents(base_path('phpstan.neon'));

    expect($config)->toContain('app/')
        ->toContain('tests/')
        ->toContain('larastan/larastan')
        ->toContain('level: 7');
});

test('phpstan has no baseline file', function () {
    // A baseline is where a real error goes to be forgotten, and D-27 records
    // that the test suite was fixed rather than baselined. Checking for the
    // include or the parameter is what actually detects one; searching for the
    // word would match the comment explaining why there is none.
    $config = (string) file_get_contents(base_path('phpstan.neon'));

    expect($config)->not->toContain('phpstan-baseline')
        ->and(file_exists(base_path('phpstan-baseline.neon')))->toBeFalse();
});

test('the production build is documented but kept out of ci:check', function () {
    // ci:check deliberately does not build: GitHub Actions runs it on every pull
    // request and a production build is the slowest step in it. Asserting the
    // omission keeps it a decision instead of letting it drift into an
    // oversight nobody notices until a broken bundle ships.
    //
    // Read from docs/roadmap rather than AGENTS.md: that file is gitignored, so
    // a test asserting against it would fail on a machine where it is missing
    // and pass on a machine where it is stale.
    $documented = (string) file_get_contents(base_path('docs/roadmap/phase-01-project-foundation.md'));

    expect($documented)->toContain('npm run build')
        ->and(composerScripts()['ci:check'] ?? '')->not->toContain('npm run build');
});
