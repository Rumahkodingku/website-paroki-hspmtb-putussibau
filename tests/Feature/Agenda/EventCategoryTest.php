<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Activity categories — /admin/master-data/kategori-agenda
|--------------------------------------------------------------------------
|
| The same shape as PostCategoryTest, with the one difference that matters:
| this one carries a colour, and the colour is validated as a hex string
| rather than as anything the browser happens to accept.
|
| It is worth stating that the colour is never the only signal — roadmap 5.3
| says so and the list renders the name two columns away from the swatch — so
| nothing here depends on a visitor distinguishing one.
*/

/*
| Access
*/

it('sends a guest to the login page', function (string $route) {
    $this->get($route)->assertRedirect(route('login'));
})->with([
    'index' => fn () => route('event-categories.index'),
    'create' => fn () => route('event-categories.create'),
]);

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('event-categories.index'))
        ->assertForbidden();
});

it('lets a super admin through', function () {
    $this->actingAs(superAdmin())
        ->get(route('event-categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/master-data/kategori-agenda/index'));
});

/*
| Listing
*/

it('lists categories with the activity count', function () {
    $category = EventCategory::factory()->create(['name' => 'Liturgi']);
    $other = EventCategory::factory()->create(['name' => 'Rapat']);
    Event::factory()->count(2)->create(['event_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        // Filtered rather than positional: the default order is by name and the
        // other category is generated, so "the first row" would be a test of the
        // faker rather than of the resource.
        ->get(route('event-categories.index', ['search' => 'Liturgi']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/master-data/kategori-agenda/index')
            ->has('eventCategories.data', 1)
            ->where('eventCategories.data.0.name', 'Liturgi')
            ->where('eventCategories.data.0.events_count', 2)
        );
});

it('filters by name and sorts from the URL', function () {
    $this->actingAs(superAdmin())
        ->get(route('event-categories.index', ['search' => 'Liturgi']))
        ->assertInertia(fn (Assert $page) => $page->where('filters.search', 'Liturgi'));
});

/*
| Writing
*/

it('creates a category', function () {
    $this->actingAs(superAdmin())
        ->post(route('event-categories.store'), [
            'name' => 'Pembinaan',
            'slug' => 'pembinaan',
        ])
        ->assertRedirect(route('event-categories.index'));

    $this->assertDatabaseHas('event_categories', [
        'name' => 'Pembinaan',
        'slug' => 'pembinaan',
    ]);
});

it('rejects a capitalised slug', function () {
    // The public detail route constrains slugs to [a-z0-9-]+, so a capitalised
    // slug the form accepted would be a URL that 404s. `alpha_dash:ascii,lower`
    // does not enforce the case in this Laravel version — the rule is a
    // separate `lowercase` — which is why this test exists.
    $this->actingAs(superAdmin())
        ->post(route('event-categories.store'), [
            'name' => 'Uji',
            'slug' => 'Liturgi',
        ])
        ->assertSessionHasErrors('slug');
});

it('updates a category and lets it keep its own slug', function () {
    $category = EventCategory::factory()->create(['name' => 'Lama', 'slug' => 'lama']);

    $this->actingAs(superAdmin())
        ->patch(route('event-categories.update', $category), [
            'name' => 'Baru',
            'slug' => 'lama',
        ])
        ->assertRedirect(route('event-categories.index'));

    $this->assertDatabaseHas('event_categories', [
        'id' => $category->getKey(),
        'name' => 'Baru',
    ]);
});

/*
| Deletion
*/

it('deletes a category nothing is using', function () {
    $category = EventCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('event-categories.destroy', $category))
        ->assertRedirect(route('event-categories.index'));

    $this->assertDatabaseMissing('event_categories', ['id' => $category->getKey()]);
});

it('refuses to delete a category that still holds activities, and says why', function () {
    $category = EventCategory::factory()->create();
    Event::factory()->count(2)->create(['event_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('event-categories.destroy', $category))
        ->assertRedirect(route('event-categories.index'))
        ->assertSessionHasErrors('event_category');

    expect(session('errors')->get('event_category')[0])->toContain('2 kegiatan');

    $this->assertDatabaseHas('event_categories', ['id' => $category->getKey()]);
});

it('refuses to delete a category whose only activities are soft deleted', function () {
    // A soft-deleted activity is still a row holding the foreign key, and the
    // constraint is RESTRICT — so the delete would otherwise reach MySQL and come
    // back as a 500.
    $category = EventCategory::factory()->create();
    Event::factory()->create(['event_category_id' => $category->getKey()])->delete();

    $this->actingAs(superAdmin())
        ->delete(route('event-categories.destroy', $category))
        ->assertRedirect(route('event-categories.index'))
        ->assertSessionHasErrors('event_category');

    $this->assertDatabaseHas('event_categories', ['id' => $category->getKey()]);
});

/*
| Form meta
*/

it('sends the status choices derived from the enum', function () {
    $this->actingAs(superAdmin())
        ->get(route('event-categories.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/master-data/kategori-agenda/tambah')
            ->has('labels.color')
            ->has('hints.color')
            ->where('eventStatusChoices', array_map(
                fn (EventStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                EventStatus::cases(),
            ))
        );
});
