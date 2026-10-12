<?php

use App\Enums\MediaStatus;
use App\Enums\PostStatus;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Articles — /admin/berita
|--------------------------------------------------------------------------
|
| The rules worth the most tests here are the ones a browser cannot check:
| that the body is sanitized on the way in (AC-POST-6), that a slug is derived
| and made unique rather than colliding (POST-01), that a scheduled article
| cannot be published with no date (PRD 9.6), and that the pinned strip is
| capped at two (POST-07).
|
| Every admin test goes through superAdmin() rather than a bare user, because
| /admin carries three middleware layers and a 403 before the policy is even
| reached says nothing about the policy.
*/

/*
|--------------------------------------------------------------------------
| Access
|--------------------------------------------------------------------------
*/

it('sends a guest to the login page', function (string $route) {
    $this->get($route)->assertRedirect(route('login'));
})->with([
    'list' => fn () => route('posts.list'),
    'create' => fn () => route('posts.create'),
]);

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('posts.list'))
        ->assertForbidden();
});

it('lets a super admin through', function () {
    $this->actingAs(superAdmin())
        ->get(route('posts.list'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/berita'));
});

it('redirects the module root to the list rather than rendering a page', function () {
    // PRD 5.2 names /admin/berita, and every breadcrumb points at it, so the
    // name and the URL stay — but it is a redirect, the shape Phase 04 settled
    // on. See D-42.
    $this->actingAs(superAdmin())
        ->get(route('posts.index'))
        ->assertRedirect(route('posts.list'));
});

/*
|--------------------------------------------------------------------------
| Writing
|--------------------------------------------------------------------------
*/

/**
 * A valid payload, so a test can change one field and say which.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validPost(array $overrides = []): array
{
    return [
        'title' => 'Mgr. Yohanes mengenangkan Gaudium Vacandi',
        'slug' => '',
        'post_category_id' => PostCategory::factory()->create()->getKey(),
        'author_name' => '',
        'excerpt' => 'Ringkasan artikel.',
        'content' => '<p>Isi artikel.</p>',
        'image_alt' => '',
        'scripture_reference' => '',
        'status' => PostStatus::Published->value,
        'published_at' => '',
        'is_pinned' => false,
        'meta_title' => '',
        'meta_description' => '',
        'media_id' => null,
        ...$overrides,
    ];
}

it('creates an article', function () {
    $user = superAdmin();

    $this->actingAs($user)
        ->post(route('posts.store'), validPost(['title' => 'Artikel Pertama']))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('posts', ['title' => 'Artikel Pertama']);

    // POST-06 / D-21: the author comes from the session, and the byline falls
    // back to that account's name rather than to an empty string.
    $post = Post::query()->firstOrFail();

    expect($post->author_id)->toBe($user->getKey())
        ->and($post->author_name)->toBe($user->name);
});

it('never takes view_count from the request', function () {
    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost(['view_count' => 9999]));

    // The field is not in the rules at all, so it is not in validated() and a
    // number the admin typed cannot become the number the card shows.
    expect(Post::query()->firstOrFail()->view_count)->toBe(0);
});

it('sanitizes the body, dropping scripts and event handlers', function () {
    // AC-POST-6 / POST-05. The arch test only proves the request layer knows
    // about the sanitizer; this is the one that proves it is called.
    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost([
            'content' => '<p>Hola</p><script>alert(1)</script><img src="x" onerror="alert(2)">',
        ]));

    $stored = Post::query()->firstOrFail()->content;

    expect($stored)
        ->toContain('Hola')
        ->not->toContain('<script')
        ->not->toContain('onerror')
        ->not->toContain('alert(1)');
});

/*
|--------------------------------------------------------------------------
| Slug — POST-01
|--------------------------------------------------------------------------
*/

it('derives a slug from the title when the field is left empty', function () {
    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost([
            'title' => 'Renungan Hari Raya Paskah',
            'slug' => '',
        ]));

    expect(Post::query()->firstOrFail()->slug)->toBe('renungan-hari-raya-paskah');
});

it('makes a derived slug unique with a numeric suffix', function () {
    $user = superAdmin();

    $this->actingAs($user)
        ->post(route('posts.store'), validPost([
            'title' => 'Pengumuman RSPP',
            'status' => PostStatus::Draft->value,
        ]));

    $this->actingAs($user)->post(route('posts.store'), validPost([
        'title' => 'Pengumuman RSPP',
        'status' => PostStatus::Draft->value,
    ]));

    $this->actingAs($user)->post(route('posts.store'), validPost([
        'title' => 'Pengumuman RSPP',
        'status' => PostStatus::Draft->value,
    ]));

    expect(
        Post::query()->orderBy('id')->pluck('slug')->all()
    )->toBe(['pengumuman-rspp', 'pengumuman-rspp-2', 'pengumuman-rspp-3']);
});

it('keeps the slug when the title of a published article is edited', function () {
    $post = Post::factory()->published()->create(['slug' => 'slug-awal']);
    $user = superAdmin();

    $this->actingAs($user)->patch(
        route('posts.update', $post),
        validPost([
            'title' => 'Judul Baru Sama Sekali',
            'slug' => $post->slug,
            'post_category_id' => $post->post_category_id,
        ])
    );

    expect($post->fresh()->title)->toBe('Judul Baru Sama Sekali')
        // The URL somebody shared still works. POST-01.
        ->and($post->fresh()->slug)->toBe('slug-awal');
});

it('refuses a slug another article holds', function () {
    Post::factory()->create(['slug' => 'sudah-terpakai']);

    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost(['slug' => 'sudah-terpakai']))
        ->assertSessionHasErrors('slug');
});

it('refuses a capitalised slug', function () {
    // alpha_dash:ascii,lower does not enforce the case in this Laravel version,
    // so the rule is a separate `lowercase`. Without it every route would 404
    // on a value the form had accepted.
    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost(['slug' => 'Judul-Besar']))
        ->assertSessionHasErrors('slug');
});

/*
|--------------------------------------------------------------------------
| Publication dates — PRD 9.6, POST-02
|--------------------------------------------------------------------------
*/

it('stamps published_at with now when publishing without a date', function () {
    Carbon::setTestNow('2026-10-11 09:00:00');

    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost([
            'status' => PostStatus::Published->value,
            'published_at' => '',
        ]));

    expect(Post::query()->firstOrFail()->published_at?->toIso8601String())
        ->toBe('2026-10-11T09:00:00+00:00');

    Carbon::setTestNow();
});

it('reads a WIB publication date as WIB and stores UTC', function () {
    // Pinned: the scheduled date below has to be in the future, and a test that
    // uses the real clock stops being future the day the calendar catches up.
    Carbon::setTestNow('2026-10-11 09:00:00');

    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost([
            'status' => PostStatus::Published->value,
            // Nine in the morning in Pontianak is two in the morning UTC.
            'published_at' => '2026-10-12T09:00',
        ]));

    expect(Post::query()->firstOrFail()->published_at?->toIso8601String())
        ->toBe('2026-10-12T02:00:00+00:00');

    Carbon::setTestNow();
});

it('leaves a draft with no publication date', function () {
    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost([
            'status' => PostStatus::Draft->value,
            'published_at' => '',
        ]));

    expect(Post::query()->firstOrFail()->published_at)->toBeNull();
});

it('requires a future date when scheduling', function (string $date, string $field) {
    Carbon::setTestNow('2026-10-11 09:00:00');

    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost([
            'status' => PostStatus::Scheduled->value,
            'published_at' => $date,
        ]))
        ->assertSessionHasErrors($field);

    expect(Post::query()->count())->toBe(0);

    Carbon::setTestNow();
})->with([
    'no date at all' => ['', 'published_at'],
    'a date in the past' => ['2026-10-10T09:00', 'published_at'],
]);

/*
|--------------------------------------------------------------------------
| Pinned — POST-07
|--------------------------------------------------------------------------
*/

it('allows two pinned articles and refuses the third', function () {
    Post::factory()->published()->pinned()->count(2)->create();

    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost(['is_pinned' => true]))
        ->assertSessionHasErrors('is_pinned');

    expect(Post::query()->pinned()->count())->toBe(2);
});

it('lets an article being edited re-save without releasing its own slot', function () {
    $post = Post::factory()->published()->pinned()->create();
    Post::factory()->published()->pinned()->create();

    // The article being edited is excluded from the count, so re-saving it does
    // not find itself in the way — which would make a pinned article
    // impossible to edit.
    $this->actingAs(superAdmin())
        ->patch(route('posts.update', $post), validPost([
            'title' => 'Judul Baru',
            'slug' => $post->slug,
            'post_category_id' => $post->post_category_id,
            'is_pinned' => true,
        ]))
        ->assertSessionHasNoErrors();

    expect($post->fresh()->title)->toBe('Judul Baru');
});

it('does not count an unpublished article towards the pinned limit', function () {
    // The strip shows published articles only, so two drafts plus one
    // published is still room for a second published.
    Post::factory()->draft()->pinned()->count(2)->create();

    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost([
            'is_pinned' => true,
            'status' => PostStatus::Published->value,
        ]))
        ->assertSessionHasNoErrors();
});

/*
|--------------------------------------------------------------------------
| Media — D-6
|--------------------------------------------------------------------------
*/

it('refuses a media reference that has not finished processing', function () {
    $media = Media::factory()->create(['status' => MediaStatus::Pending]);

    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost(['media_id' => $media->getKey()]))
        ->assertSessionHasErrors('media_id');
});

it('accepts a media reference that is ready', function () {
    $media = Media::factory()->create(['status' => MediaStatus::Ready]);

    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost([
            'media_id' => $media->getKey(),
            'status' => PostStatus::Draft->value,
        ]))
        ->assertSessionHasNoErrors();
});

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

it('rejects invalid input and writes nothing', function (array $overrides, string $field) {
    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost($overrides))
        ->assertSessionHasErrors($field);

    expect(Post::query()->count())->toBe(0);
})->with([
    'no title' => [['title' => ''], 'title'],
    'no category' => [['post_category_id' => ''], 'post_category_id'],
    'a category that does not exist' => [['post_category_id' => 9999], 'post_category_id'],
    'no content' => [['content' => ''], 'content'],
    'no status' => [['status' => ''], 'status'],
    'a status outside the enum' => [['status' => 'menunggu'], 'status'],
]);

it('requires a category that exists', function () {
    $this->actingAs(superAdmin())
        ->post(route('posts.store'), validPost([
            'post_category_id' => 999_999,
        ]))
        ->assertSessionHasErrors('post_category_id');
});

/*
|--------------------------------------------------------------------------
| Editing
|--------------------------------------------------------------------------
*/

it('updates an article and keeps its image when none is chosen again', function () {
    $post = Post::factory()->create([
        'title' => 'Lama',
        'slug' => 'lama',
        'featured_image' => 'media/variants/1/large.webp',
    ]);

    $this->actingAs(superAdmin())
        ->patch(route('posts.update', $post), validPost([
            'title' => 'Baru',
            'slug' => 'lama',
            'post_category_id' => $post->post_category_id,
            'media_id' => null,
        ]))
        ->assertSessionHasNoErrors();

    // A null media_id means "unchanged", not "remove my picture". That is why
    // featured_image is dropped from the payload and written by the action.
    expect($post->fresh()->title)->toBe('Baru')
        ->and($post->fresh()->featured_image)->toBe('media/variants/1/large.webp');
});

it('sends the record to the edit form and nothing else', function () {
    $post = Post::factory()->create(['title' => 'Artikel']);

    $this->actingAs(superAdmin())
        ->get(route('posts.edit', $post))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/berita/ubah')
            ->where('post.id', $post->getKey())
            ->where('post.title', 'Artikel')
            // No image means no URL, not the storage root.
            ->where('post.featured_image_url', null)
            ->has('postCategories')
        );
});

it('sends the featured image as a browser url for the picker', function () {
    $post = Post::factory()->create([
        'featured_image' => 'media/variants/9/large.webp',
    ]);

    $this->actingAs(superAdmin())
        ->get(route('posts.edit', $post))
        ->assertInertia(fn (Assert $page) => $page
            // The path stays the stored contract; the URL is what <img> reads.
            ->where('post.featured_image', 'media/variants/9/large.webp')
            ->where(
                'post.featured_image_url',
                Storage::disk('public')->url('media/variants/9/large.webp'),
            )
        );
});

it('refuses an update from a user without the ability', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->patch(route('posts.update', Post::factory()->create()), validPost())
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Deleting
|--------------------------------------------------------------------------
*/

it('soft deletes an article and hides it', function () {
    $post = Post::factory()->published()->create();

    $this->actingAs(superAdmin())
        ->delete(route('posts.destroy', $post))
        ->assertRedirect(route('posts.list'));

    expect(Post::query()->count())->toBe(0)
        // The row stays, and with it the slug — see D-45.
        ->and(Post::withTrashed()->count())->toBe(1);
});

it('refuses a delete from a user without the ability', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->delete(route('posts.destroy', Post::factory()->create()))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Listing
|--------------------------------------------------------------------------
*/

it('lists every article regardless of status', function () {
    Post::factory()->count(3)->create();

    $this->actingAs(superAdmin())
        ->get(route('posts.list'))
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 3));
});

it('filters the list by status, category and search', function () {
    $category = PostCategory::factory()->create();
    Post::factory()->draft()->create(['title' => 'Draf']);

    $published = Post::factory()->published()->create([
        'title' => 'Sudah Terbit',
        'post_category_id' => $category->getKey(),
    ]);

    $this->actingAs(superAdmin())
        ->get(route('posts.list', ['status' => 'published']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.id', $published->getKey())
        );

    $this->actingAs(superAdmin())
        ->get(route('posts.list', ['search' => 'Terbit']))
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));

    $this->actingAs(superAdmin())
        ->get(route('posts.list', ['kategori' => (string) $category->getKey()]))
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
});

it('ignores a status outside the enum rather than erroring', function () {
    Post::factory()->count(2)->create();

    $this->actingAs(superAdmin())
        ->get(route('posts.list', ['status' => 'entah']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
});

it('ignores an unknown sort key rather than failing', function () {
    Post::factory()->create();

    // D-42's lesson: a leftover whitelist entry for a dropped column turns a
    // bookmarked link into a 500.
    $this->actingAs(superAdmin())
        ->get(route('posts.list', ['sort' => 'language']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
});

/*
|--------------------------------------------------------------------------
| Upload
|--------------------------------------------------------------------------
*/

it('rejects an upload whose bytes are not a processable image', function () {
    $this->actingAs(superAdmin())
        ->postJson(route('media.store'), [
            'file' => UploadedFile::fake()->create('bukan-gambar.jpg', 10),
        ])
        ->assertStatus(422);
});
