<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Inertia Shared Props
|--------------------------------------------------------------------------
|
| Phase 01 section H requires shared props, and ARCHITECTURE.md Part C rule 1
| forbids handing a raw Eloquent model to the browser. The authenticated user
| is the prop that matters: everything in these props is serialised into the
| page source and is readable by anyone who opens devtools.
|
| The point of the first test is that it pins the exact key list. A future
| column, or a relation somebody loads onto the user, must fail here rather
| than quietly start shipping.
|
| @see docs/DECISIONS.md D-21
|
*/

test('the authenticated user is shared with only the agreed fields', function () {
    $user = superAdmin();

    $response = $this->actingAs($user)->get(route('dashboard'));

    // Read the resolved payload rather than the Inertia test helper, because
    // shared props are closures and this is what actually reaches the browser.
    $shared = $response->viewData('page')['props']['auth']['user'];

    // The exact list, not just presence. A new column, or a relation somebody
    // loads onto the user, must fail here instead of quietly starting to ship.
    expect(array_keys($shared))->toBe([
        'id',
        'name',
        'email',
        'email_verified_at',
    ]);

    expect($shared['id'])->toBe($user->id)
        ->and($shared['email'])->toBe($user->email);
});

test('shared user props never contain credentials or internal flags', function () {
    $user = superAdmin();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $shared = $response->viewData('page')['props']['auth']['user'];

    expect($shared)->not->toHaveKey('password')
        ->and($shared)->not->toHaveKey('remember_token')
        ->and($shared)->not->toHaveKey('is_active')
        ->and($shared)->not->toHaveKey('created_at')
        ->and($shared)->not->toHaveKey('updated_at');
});

test('the shared user carries the verified timestamp as an ISO 8601 string', function () {
    $user = superAdmin();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $verifiedAt = $response->viewData('page')['props']['auth']['user']['email_verified_at'];

    expect($verifiedAt)->toBeString()
        ->and($verifiedAt)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/');
});

test('an unverified account shares a null verified timestamp', function () {
    $user = superAdmin(['email_verified_at' => null]);

    // Every admin page sits behind the verified middleware, so an unverified
    // account only ever reaches the notice page. That is where the shared
    // contract has to hold for it.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/auth/verify-email')
            ->where('auth.user.email_verified_at', null)
        );
});

test('a guest receives a null user rather than a missing key', function () {
    // welcome is the only page a guest can reach, so it is where the guest
    // contract has to hold.
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->has('auth')
            ->where('auth.user', null)
        );
});

test('the locale and display timezone are shared', function () {
    $this->actingAs(superAdmin())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'id')
            // Storage is UTC (D-01); this is what React must render dates with.
            ->where('displayTimezone', 'Asia/Pontianak')
        );
});

test('a successful mutation shares a flash toast', function () {
    $user = superAdmin();

    // Both fields are required by ProfileUpdateRequest, so a name-only payload
    // would bounce on validation and never reach the flash call.
    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => $user->email,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasNoErrors()
        // Inertia v3 keeps flash out of page props and out of history state.
        // It arrives as a flash event, so this is where it is verifiable.
        ->assertInertiaFlash('toast.type', 'success');
});

test('no page leaks a column the shared contract does not name', function () {
    // Guards the contract against silent widening: every column on the table is
    // either shared on purpose or withheld on purpose.
    $user = superAdmin();
    $shared = ['id', 'name', 'email', 'email_verified_at'];

    $columns = Schema::getColumnListing('users');

    $withheld = array_diff($columns, $shared);

    expect($withheld)->toContain('password')
        ->and($withheld)->toContain('is_active');

    $payload = $this->actingAs($user)->get(route('dashboard'))->viewData('page')['props'];

    foreach ($withheld as $column) {
        expect($payload['auth']['user'])->not->toHaveKey($column);
    }
});
