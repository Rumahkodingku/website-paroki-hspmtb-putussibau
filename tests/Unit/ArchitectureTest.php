<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
