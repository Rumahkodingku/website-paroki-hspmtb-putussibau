<?php

use App\Models\Livestream;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Livestreaming — otorisasi
|--------------------------------------------------------------------------
|
| ARCHITECTURE Part D section 2: every new route needs its guest and
| unauthorized cases, not only its success path. Hiding a button in React is
| not authorization; these requests hit the backend directly.
|
| @see docs/DECISIONS.md D-101
*/

it('redirects a guest from the admin livestreaming pages', function () {
    $stream = Livestream::factory()->create();

    $this->get(route('livestreams.index'))->assertRedirect(route('login'));
    $this->get(route('livestreams.create'))->assertRedirect(route('login'));
    $this->get(route('livestreams.edit', $stream))->assertRedirect(route('login'));
    $this->post(route('livestreams.store'), [])->assertRedirect(route('login'));
});

it('refuses a signed-in user without admin access', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('livestreams.index'))
        ->assertForbidden();
});

it('refuses an admin without livestream permissions on every endpoint', function () {
    seedRolesAndPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');

    $stream = Livestream::factory()->create();

    $this->actingAs($user)->get(route('livestreams.index'))->assertForbidden();
    $this->actingAs($user)->get(route('livestreams.create'))->assertForbidden();
    $this->actingAs($user)->post(route('livestreams.store'), [])->assertForbidden();
    $this->actingAs($user)->get(route('livestreams.edit', $stream))->assertForbidden();
    $this->actingAs($user)->patch(route('livestreams.update', $stream), [])->assertForbidden();
    $this->actingAs($user)->delete(route('livestreams.destroy', $stream))->assertForbidden();
    $this->actingAs($user)->post(route('livestreams.start', $stream))->assertForbidden();
    $this->actingAs($user)->post(route('livestreams.end', $stream))->assertForbidden();
    $this->actingAs($user)->post(route('livestreams.unavailable', $stream))->assertForbidden();
});

it('does not create a record when storage is unauthorized', function () {
    seedRolesAndPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');

    $this->actingAs($user)
        ->post(route('livestreams.store'), ['title' => 'Tidak boleh'])
        ->assertForbidden();

    expect(Livestream::query()->count())->toBe(0);
});

it('lets a super admin open the admin pages', function () {
    $this->withoutVite();

    $stream = Livestream::factory()->create();

    $this->actingAs(superAdmin())
        ->get(route('livestreams.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/livestreaming')
            ->has('livestreams.data')
            ->has('categories')
            ->has('statuses')
            ->has('broadcastStatuses')
            ->where('activeLive', null));

    $this->actingAs(superAdmin())
        ->get(route('livestreams.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/livestreaming/tambah'));

    $this->actingAs(superAdmin())
        ->get(route('livestreams.edit', $stream))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/livestreaming/ubah')
            ->where('livestream.id', $stream->getKey()));
});
