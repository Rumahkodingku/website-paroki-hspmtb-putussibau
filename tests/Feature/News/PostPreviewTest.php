<?php

use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| The editor's preview
|--------------------------------------------------------------------------
|
| A preview exists so an article can be read at its published width before it
| is live. That gives it one job the public route cannot do — show a draft —
| and one job that is easy to get wrong: it must not count as a view, and it
| must not leak to anyone who is not an administrator.
|
| The view-count half is asserted here as well as in ViewCountTest. It is the
| same behaviour seen from the other side, and ViewCountTest's version can be
| deleted without anyone noticing the preview path stopped working.
|
| @see docs/PRD.md 7.4
*/

/*
|--------------------------------------------------------------------------
| Access
|--------------------------------------------------------------------------
*/

it('lets an administrator preview a draft', function () {
    seedRolesAndPermissions();

    $post = Post::factory()->draft()->create(['title' => 'BELUM TERBIT']);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('posts.preview', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/berita/preview')
            ->where('post.id', $post->getKey())
            ->where('post.title', 'BELUM TERBIT')
        );
});

it('turns a guest away from the preview', function () {
    $post = Post::factory()->draft()->create();

    $this->get(route('posts.preview', $post))->assertRedirect(route('login'));
});

it('turns away an authenticated account with no admin access', function () {
    seedRolesAndPermissions();

    $post = Post::factory()->draft()->create();
    $reader = User::factory()->create();

    // Only super_admin exists on the MVP, so this is the shape a second role
    // will take. The preview renders unpublished content, so it must not be
    // reachable by anyone who merely has an account.
    $this->actingAs($reader)
        ->get(route('posts.preview', $post))
        ->assertForbidden();
});

it('does not let the preview stand in for the public route', function () {
    seedRolesAndPermissions();

    $post = Post::factory()->draft()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    // The preview works and the public URL does not. Asserted together because
    // the interesting bug is a preview that leaks by making the draft public,
    // which each test on its own would miss.
    $this->actingAs($admin)->get(route('posts.preview', $post))->assertOk();
    $this->get(route('berita.show', $post))->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| The view count — POST-13
|--------------------------------------------------------------------------
*/

it('does not count a preview as a view', function () {
    seedRolesAndPermissions();

    $post = Post::factory()->draft()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)->get(route('posts.preview', $post))->assertOk();
    $this->actingAs($admin)->get(route('posts.preview', $post))->assertOk();

    // Authors read their work repeatedly before publishing it. Counting that
    // is the difference between a readership number and a counter.
    expect($post->fresh()->view_count)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Content
|--------------------------------------------------------------------------
*/

it('shows the same body the article will publish', function () {
    seedRolesAndPermissions();

    $post = Post::factory()->draft()->create([
        'title' => 'Renungan Jumat',
        'content' => '<p>Belgiau, hari Jumat.</p>',
        'scripture_reference' => 'Yohanes 15.13',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('posts.preview', $post))
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.content', '<p>Belgiau, hari Jumat.</p>')
            ->where('post.scripture_reference', 'Yohanes 15.13')
        );
});

it('marks a draft as not yet published', function () {
    seedRolesAndPermissions();

    $post = Post::factory()->draft()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    // Not post.status: the preview resolves through PublicPostDetailResource,
    // deliberately, so that what an editor reads is exactly what a visitor will
    // get. The public shape has no status field and adding one would make the
    // preview drift from the page it is previewing. `isPublished` exists for
    // the banner instead.
    $this->actingAs($admin)
        ->get(route('posts.preview', $post))
        ->assertInertia(fn (Assert $page) => $page->where('isPublished', false));
});

it('marks a published article as published', function () {
    seedRolesAndPermissions();

    $post = Post::factory()->published()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('posts.preview', $post))
        ->assertInertia(fn (Assert $page) => $page->where('isPublished', true));
});

it('carries the same keys as the public article page', function () {
    seedRolesAndPermissions();

    $post = Post::factory()->draft()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $preview = $this->actingAs($admin)
        ->get(route('posts.preview', $post))
        ->assertOk()
        ->viewData('page')['props']['post'];

    $public = $this->get(route('berita.show', $post))->assertNotFound();

    // A published sibling, so the public page can actually be rendered for the
    // comparison. Comparing the key *sets* is the point: the preview page
    // reads these keys, and a key the public page does not send is a key the
    // preview renders as undefined.
    $sibling = Post::factory()->published()->create();
    $publicKeys = array_keys(
        $this->get(route('berita.show', $sibling))
            ->assertOk()
            ->viewData('page')['props']['post']
    );

    expect(array_keys($preview))->toEqual($publicKeys);
});

it('answers 404 for an article that does not exist', function () {
    seedRolesAndPermissions();

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)->get('/admin/berita/999999/pratinjau')->assertNotFound();
});

it('answers 404 for a soft-deleted article', function () {
    seedRolesAndPermissions();

    $post = Post::factory()->published()->create()->delete();
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    // A deleted article is not previewable from the trash. If an editor needs
    // to read a deleted article back, that is a restore and then a preview —
    // not a hole in the soft-delete rule.
    $this->actingAs($admin)->get(route('posts.preview', $post))->assertNotFound();
});
