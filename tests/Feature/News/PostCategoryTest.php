<?php

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Article categories — /admin/master-data/kategori-berita
|--------------------------------------------------------------------------
|
| The rule this screen exists to enforce is the one at the bottom: a category
| that still holds articles cannot be deleted. PRD 7.4 asks for the admin to
| manage categories, and roadmap 5.5 says the ones in use are refused with a
| message rather than a database error — which is the difference between a
| sentence an admin can act on and a 500 they can only report.
*/

/*
| Access
*/

it('sends a guest to the login page', function (string $route) {
    $this->get($route)->assertRedirect(route('login'));
})->with([
    'index' => fn () => route('post-categories.index'),
    'create' => fn () => route('post-categories.create'),
]);

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('post-categories.index'))
        ->assertForbidden();
});

it('lets a super admin through', function () {
    $this->actingAs(superAdmin())
        ->get(route('post-categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/master-data/kategori-berita/index'));
});

/*
| Listing
*/

it('lists categories ordered by sort_order with the article count', function () {
    PostCategory::factory()->create(['name' => 'Pengumuman', 'sort_order' => 4]);
    $berita = PostCategory::factory()->create(['name' => 'Berita', 'sort_order' => 1]);
    Post::factory()->count(2)->create(['post_category_id' => $berita->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('post-categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/master-data/kategori-berita/index')
            // where() rather than has(x, 2): the count has to be on the right
            // row, and a `has('posts_count', 2)` would pass on a row that has
            // one article and another that has none.
            ->where('postCategories.data.0.name', 'Berita')
            ->where('postCategories.data.0.posts_count', 2)
            ->where('postCategories.data.1.name', 'Pengumuman')
            ->where('postCategories.data.1.posts_count', 0)
        );
});

it('filters the list by name', function () {
    PostCategory::factory()->create(['name' => 'Renungan']);
    PostCategory::factory()->create(['name' => 'Kegiatan']);

    $this->actingAs(superAdmin())
        ->get(route('post-categories.index', ['search' => 'Renung']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('postCategories.data.0.name', 'Renungan')
            ->has('postCategories.data', 1)
        );
});

it('sorts by name and slug from the URL', function (string $key) {
    $this->actingAs(superAdmin())
        ->get(route('post-categories.index', ['sort' => $key]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.sort', $key));
})->with(['name', 'slug', 'sort_order']);

it('ignores an unknown sort key instead of failing', function () {
    PostCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->get(route('post-categories.index', ['sort' => 'kolom-yang-tidak-ada']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('postCategories.data', 1));
});

/*
| Writing
*/

it('creates a category', function () {
    $this->actingAs(superAdmin())
        ->post(route('post-categories.store'), [
            'name' => 'Renungan',
            'slug' => 'renungan',
            'sort_order' => 3,
        ])
        ->assertRedirect(route('post-categories.index'));

    $this->assertDatabaseHas('post_categories', [
        'name' => 'Renungan',
        'slug' => 'renungan',
        'sort_order' => 3,
    ]);
});

it('rejects invalid input and writes nothing', function (array $payload, string $field) {
    $this->actingAs(superAdmin())
        ->post(route('post-categories.store'), $payload)
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('post_categories', 0);
})->with([
    'no name' => [['name' => '', 'slug' => 'x', 'sort_order' => 0], 'name'],
    'capital slug' => [['name' => 'X', 'slug' => 'Kategori', 'sort_order' => 0], 'slug'],
    'slug with a space' => [['name' => 'X', 'slug' => 'dua kata', 'sort_order' => 0], 'slug'],
    'negative order' => [['name' => 'X', 'slug' => 'x', 'sort_order' => -1], 'sort_order'],
]);

it('refuses a duplicate slug', function () {
    PostCategory::factory()->create(['slug' => 'renungan']);

    $this->actingAs(superAdmin())
        ->post(route('post-categories.store'), [
            'name' => 'Renungan',
            'slug' => 'renungan',
            'sort_order' => 1,
        ])
        ->assertSessionHasErrors('slug');
});

it('updates a category and lets it keep its own slug', function () {
    $category = PostCategory::factory()->create(['name' => 'Lama', 'slug' => 'lama']);

    $this->actingAs(superAdmin())
        ->patch(route('post-categories.update', $category), [
            'name' => 'Baru',
            'slug' => 'lama',
            'sort_order' => 2,
        ])
        ->assertRedirect(route('post-categories.index'));

    $this->assertDatabaseHas('post_categories', [
        'id' => $category->getKey(),
        'name' => 'Baru',
        'sort_order' => 2,
    ]);
});

/*
| Deletion
*/

it('deletes a category nothing is using', function () {
    $category = PostCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('post-categories.destroy', $category))
        ->assertRedirect(route('post-categories.index'));

    $this->assertDatabaseMissing('post_categories', ['id' => $category->getKey()]);
});

it('refuses to delete a category that still holds articles, and says why', function () {
    $category = PostCategory::factory()->create();
    Post::factory()->count(2)->create(['post_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('post-categories.destroy', $category))
        ->assertRedirect(route('post-categories.index'))
        ->assertSessionHasErrors('post_category');

    expect(session('errors')->get('post_category')[0])
        ->toContain('2 artikel');

    $this->assertDatabaseHas('post_categories', ['id' => $category->getKey()]);
});

it('refuses to delete a category whose only articles are soft deleted', function () {
    // Not "succeeds" — the opposite. A soft-deleted article is still a row
    // holding posts.post_category_id, and the foreign key is RESTRICT, so the
    // delete would reach MySQL and come back as a QueryException. That is a 500
    // on a screen whose whole job is to avoid one, and it is invisible until an
    // admin deletes their last article and then tries to tidy up.
    $category = PostCategory::factory()->create();
    Post::factory()->create(['post_category_id' => $category->getKey()])->delete();

    $this->actingAs(superAdmin())
        ->delete(route('post-categories.destroy', $category))
        ->assertRedirect(route('post-categories.index'))
        ->assertSessionHasErrors('post_category');

    $this->assertDatabaseHas('post_categories', ['id' => $category->getKey()]);
});

it('refuses a delete from a user without the ability', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->delete(route('post-categories.destroy', PostCategory::factory()->create()))
        ->assertForbidden();
});

it('lets a super admin create a category without the permission being attached by hand', function () {
    // The permission is read through can(), which the Gate::before grant
    // covers. It is deliberately NOT attached to the role — D-16 — so a test
    // that only checks hasPermissionTo() would pass while this failed.
    expect(superAdmin()->can('create', PostCategory::class))->toBeTrue();
});

it('sends the form meta the category screens need', function () {
    $this->actingAs(superAdmin())
        ->get(route('post-categories.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/master-data/kategori-berita/tambah')
            ->has('labels.name')
            ->has('hints.sort_order')
            // Derived from the enum rather than typed twice, so a new status
            // cannot appear without a label.
            ->where('postStatusChoices', array_map(
                fn (PostStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                PostStatus::cases(),
            ))
        );
});

it('sends the edit form one row and no list', function () {
    // The edit page needs the record it is editing, not the whole table. Both
    // props on one page is how a dropdown ends up carrying a can block.
    $category = PostCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->get(route('post-categories.edit', $category))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/master-data/kategori-berita/ubah')
            ->where('postCategory.id', $category->getKey())
            ->missing('postCategories')
        );
});
