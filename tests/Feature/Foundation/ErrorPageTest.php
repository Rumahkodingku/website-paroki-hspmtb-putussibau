<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Error pages
|--------------------------------------------------------------------------
|
| Test Matrix covered here:
|
|   - T27 a 404 is in Bahasa Indonesia
|   - T28 a 500 is in Bahasa Indonesia
|
| plus 403 and 419, which this application produces on real paths, and the two
| conditions under which the page must *not* appear.
|
| What these tests can and cannot prove is worth stating plainly. They assert
| the status code, the Inertia component, and the status prop, which is the
| whole backend contract. They cannot assert the Indonesian wording, because
| React renders after hydration and this project deliberately has no frontend
| test runner: the strings only exist in the browser. Asserting them from PHP
| would mean grepping a .tsx file, which is a test that passes when the file is
| edited and fails when nobody touched the test.
|
| So the language requirement is covered by construction and by the status-code
| contract, and the copy itself is reviewed by eye. That is a real gap and it is
| recorded as one rather than papered over with a source-text assertion.
|
| @see docs/DECISIONS.md D-26
|
*/

/*
 * The page renders only when debug is off, which is the production condition.
 * phpunit.xml does not set APP_DEBUG, so the suite inherits true from .env and
 * every test here would otherwise be asserting against the framework's own error
 * page and pass for the wrong reason.
 */
beforeEach(function () {
    config(['app.debug' => false]);
});

/**
 * A route that fails, so the 500 path has something to exercise.
 *
 * Registered here rather than in routes/web.php so it does not exist in the
 * application at all. A test-only route left in web.php is a route that ships.
 */
beforeEach(function () {
    Route::middleware('web')->get('/__test/gagal', function () {
        throw new RuntimeException('Pesan internal yang tidak boleh muncul.');
    });
});

/*
|--------------------------------------------------------------------------
| T27 — 404
|--------------------------------------------------------------------------
*/

test('an unknown address is a 404 rendered as the Indonesian error page', function () {
    // T27
    $this->get('/alamat-yang-tidak-ada')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/error')
            ->where('status', 404)
        );
});

test('the 404 page keeps its status rather than answering 200', function () {
    // A page that renders but answers 200 is the failure mode that is easiest
    // to ship and hardest to notice, because everything looks fine in devtools.
    $response = $this->get('/alamat-yang-tidak-ada');

    expect($response->getStatusCode())->toBe(404);
});

test('the error page is rendered for a route that aborts with 404', function () {
    Route::middleware('web')->get('/__test/abort', fn () => abort(404));

    $this->get('/__test/abort')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/error')
            ->where('status', 404)
        );
});

/*
|--------------------------------------------------------------------------
| T28 — 500
|--------------------------------------------------------------------------
*/

test('a failing route is a 500 rendered as the Indonesian error page', function () {
    // T28
    $this->get('/__test/gagal')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/error')
            ->where('status', 500)
        );
});

test('the 500 response carries no exception detail', function () {
    // The handler sends the status and nothing else, so there is no message to
    // redact. This is the assertion that would catch someone adding the
    // exception to the props to "help with debugging" later.
    $body = $this->get('/__test/gagal')->getContent();

    expect($body)->not->toContain('Pesan internal yang tidak boleh muncul.')
        ->and($body)->not->toContain('RuntimeException')
        ->and($body)->not->toContain('vendor/laravel/framework');
});

test('the 500 page does not report the status as 404', function () {
    $page = $this->get('/__test/gagal')->assertInertia();

    expect($page->viewData('page')['props']['status'])->toBe(500);
});

/*
|--------------------------------------------------------------------------
| 403 and 419
|--------------------------------------------------------------------------
*/

test('a signed in user without the role gets the Indonesian forbidden page', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/error')
            ->where('status', 403)
        );
});

test('an expired session gets the Indonesian page expired page', function () {
    Route::middleware('web')->get('/__test/expired', fn () => abort(419));

    $this->get('/__test/expired')
        ->assertStatus(419)
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/error')
            ->where('status', 419)
        );
});

/*
|--------------------------------------------------------------------------
| The two conditions under which the page must not appear
|--------------------------------------------------------------------------
*/

test('a json request is answered with json rather than an html page', function () {
    // An XHR that receives an HTML error body is the kind of thing that shows
    // up as a parse error far from its cause.
    $this->getJson('/alamat-yang-tidak-ada')
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json');
});

test('the framework page is kept while debug is on', function () {
    // Debug is the only condition that switches ours off, because the framework
    // page is the more useful one while somebody is debugging.
    config(['app.debug' => true]);

    $this->get('/alamat-yang-tidak-ada')
        ->assertNotFound()
        ->assertDontSee('data-page', escape: false);
});

test('the error page still renders without a debug session cookie', function () {
    // The page is reached by guests. If it needed session state it would 500 on
    // the way to reporting a 404.
    $this->get('/alamat-yang-tidak-ada')->assertNotFound();
});
