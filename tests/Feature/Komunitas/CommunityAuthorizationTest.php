<?php

use App\Models\Area;
use App\Models\Community;
use App\Models\CommunityCategory;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Otorisasi modul Komunitas
|--------------------------------------------------------------------------
|
| ARCHITECTURE Part D section 2: every new route needs its guest and
| unauthorized cases, not only its success path.
|
| @see docs/DECISIONS.md D-55
*/

it('redirects a guest from the community list', function () {
    $this->get(route('communities.index'))->assertRedirect(route('login'));
    $this->get(route('community-categories.index'))->assertRedirect(route('login'));
    $this->get(route('areas.index'))->assertRedirect(route('login'));
});

it('redirects a guest from creating a community', function () {
    $this->post(route('communities.store'), [])->assertRedirect(route('login'));
});

it('refuses a signed-in user without the super admin role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('communities.index'))->assertForbidden();
    $this->actingAs($user)->get(route('community-categories.index'))->assertForbidden();
    $this->actingAs($user)->get(route('areas.index'))->assertForbidden();
});

it('refuses a signed-in user from storing a community', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('communities.store'), [
            'name' => 'X',
            'slug' => 'x',
            'community_category_id' => $category->getKey(),
        ])
        ->assertForbidden();
});

it('refuses a signed-in user from deleting each entity', function () {
    $community = Community::factory()->create();
    $category = CommunityCategory::factory()->create();
    $area = Area::factory()->create();

    $user = User::factory()->create();

    $this->actingAs($user)->delete(route('communities.destroy', $community))->assertForbidden();
    $this->actingAs($user)->delete(route('community-categories.destroy', $category))->assertForbidden();
    $this->actingAs($user)->delete(route('areas.destroy', $area))->assertForbidden();
});
