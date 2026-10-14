<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Otorisasi modul Pelayanan
|--------------------------------------------------------------------------
|
| ARCHITECTURE Part D section 2: every new route needs its guest and
| unauthorized cases, not only its success path.
|
| @see docs/DECISIONS.md D-54
*/

it('redirects a guest from the service list', function () {
    $this->get(route('services.index'))->assertRedirect(route('login'));
});

it('redirects a guest from creating a service', function () {
    $this->post(route('services.store'), [])->assertRedirect(route('login'));
});

it('refuses a signed-in user without the super admin role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('services.index'))->assertForbidden();
    $this->actingAs($user)->get(route('service-categories.index'))->assertForbidden();
});

it('refuses a signed-in user from a child endpoint', function () {
    $service = Service::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('service-requirements.store', $service), ['text' => 'Berkas'])
        ->assertForbidden();
});

it('refuses the category delete when the user cannot delete', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('service-categories.destroy', $category))
        ->assertForbidden();
});
