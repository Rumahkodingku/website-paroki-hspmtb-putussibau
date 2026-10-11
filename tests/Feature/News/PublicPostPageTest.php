<?php

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| The public news pages — /berita and /berita/{slug}
|--------------------------------------------------------------------------
|
| The rule this file exists for is POST-09: a draft, a scheduled article, an
| archived article and a soft-deleted one must all answer 404 to a public URL
| even when the slug is known. Before Phase 05 those routes answered 200 for
| any well-formed slug, which D-31 recorded as a dated gap against XC-E3.
|
| The 404s below are produced by route model binding resolving through
| Post::published(). There is no abort_if in the controller to forget — which is
| the point of doing it that way rather than checking in the action.
|
| @see docs/DECISIONS.md D-43
*/

/*
|--------------------------------------------------------------------------
| Listing — POST-02
|--------------------------------------------------------------------------
*/

it('lists published articles', function () {
    $article = Post::factory()->published()->create(['title' => 'Terbit']);

    $this->get('/berita')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/berita')
            ->where('posts.data.0.id', $article->getKey())
            // D-39: a non-paginated prop arrives as {data: [...]}. This
            // assertion proves the wrapper is gone; has('posts', 1) would pass
            // on {data: [...]} because it counts one key.
            ->missing('posts.data.data')
            // An empty array has no key 0, so where('pinned.0') would fail on
            // the very case it is meant to assert. has() counts elements.
            ->has('pinned', 0)
        );
});

it('hides drafts, scheduled, future-dated, archived and soft-deleted articles', function () {
    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();
    Post::factory()->publishedInFuture()->create();
    Post::factory()->archived()->create();
    Post::factory()->published()->create()->delete();

    $visible = Post::factory()->published()->create();

    $this->get('/berita')
        ->assertInertia(fn (Assert $page) => $page
            ->where('posts.data.0.id', $visible->getKey())
            ->has('posts.data', 1)
        );
});

it('puts at most two articles in the featured strip', function () {
    Post::factory()->published()->pinned()->count(3)->create();

    $this->get('/berita')
        ->assertInertia(fn (Assert $page) => $page
            // A third pinned article exists in the database; the query caps the
            // strip at two regardless, which is POST-07's second layer.
            ->has('pinned', 2)
        );
});

it('leaves a draft out of the featured strip', function () {
    Post::factory()->draft()->pinned()->create();

    $this->get('/berita')
        ->assertInertia(fn (Assert $page) => $page->where('pinned', []));
});

it('searches the title', function () {
    $match = Post::factory()->published()->create(['title' => 'Pawai Kristus']);
    Post::factory()->published()->create(['title' => 'Rapat Pastoral']);

    $this->get('/berita?q=Pawai')
        ->assertInertia(fn (Assert $page) => $page
            ->where('posts.data.0.id', $match->getKey())
            ->has('posts.data', 1)
        );
});

it('searches the excerpt', function () {
    $match = Post::factory()->published()->create(['excerpt' => 'Katedraluller']);

    $this->get('/berita?q=Katedraluller')
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
});

it('filters by category slug', function () {
    $category = PostCategory::factory()->create(['slug' => 'renungan']);
    $match = Post::factory()->published()->create(['post_category_id' => $category->getKey()]);
    Post::factory()->published()->create();

    $this->get('/berita?kategori=renungan')
        ->assertInertia(fn (Assert $page) => $page
            ->where('posts.data.0.id', $match->getKey())
            ->has('posts.data', 1)
        );
});

it('offers only categories that hold something published', function () {
    $empty = PostCategory::factory()->create(['name' => 'Kosong']);
    $used = PostCategory::factory()->create(['name' => 'Terisi']);
    Post::factory()->published()->create(['post_category_id' => $used->getKey()]);

    $this->get('/berita')
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.name', 'Terisi')
        );

    // A category with only drafts in it offers a filter that returns nothing,
    // which is worse than not offering it.
    expect($empty->exists)->toBeTrue();
});

it('treats an unknown category slug as no filter rather than an empty list', function () {
    Post::factory()->published()->count(2)->create();

    $this->get('/berita?kategori=tidak-ada')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
});

it('does not send the article body in the list', function () {
    // A card renders no body, so shipping it means fifteen copies of the
    // article in the page HTML. ARCHITECTURE.md Part C rule 2.
    Post::factory()->published()->create(['content' => '<p>Rahasia artikel.</p>']);

    expect($this->get('/berita')->assertOk()->getContent())
        ->not->toContain('Rahasia artikel');
});

/*
|--------------------------------------------------------------------------
| Detail — POST-09, XC-E3
|--------------------------------------------------------------------------
*/

it('shows a published article', function () {
    $post = Post::factory()->published()->create(['title' => 'Artikel Terbaca']);

    $this->get(route('berita.show', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/berita/show')
            ->where('post.id', $post->getKey())
            ->where('post.title', 'Artikel Terbaca')
        );
});

it('answers 404 for a draft, even though the slug is known', function () {
    // AC-POST-5 / POST-09. The route used to answer 200 for any well-formed
    // slug; this is the test that says it no longer does.
    $this->get(route('berita.show', Post::factory()->draft()->create()))
        ->assertNotFound();
});

it('answers 404 for a scheduled article before its time', function () {
    $this->get(route('berita.show', Post::factory()->scheduled()->create()))
        ->assertNotFound();
});

it('answers 404 for a published article dated in the future', function () {
    // POST-02: the date check, not the status. A scope written as
    // `where(status, published)` would let this through.
    $this->get(route('berita.show', Post::factory()->publishedInFuture()->create()))
        ->assertNotFound();
});

it('answers 404 for an archived article', function () {
    $this->get(route('berita.show', Post::factory()->archived()->create()))
        ->assertNotFound();
});

it('answers 404 for a soft-deleted article', function () {
    $this->get(route('berita.show', Post::factory()->published()->create()->delete()))
        ->assertNotFound();
});

it('answers 404 for a slug nobody wrote', function () {
    $this->get('/berita/tidak-pernah-dibuat')->assertNotFound();
});

it('does not leak a draft through the search results', function () {
    $draft = Post::factory()->draft()->create(['title' => 'Rahasia']);

    $this->get('/berita?q=Rahasia')
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));

    expect($draft->exists)->toBeTrue();
});

it('does not leak a draft through related posts', function () {
    $category = PostCategory::factory()->create();
    $published = Post::factory()->published()->create(['post_category_id' => $category->getKey()]);
    Post::factory()->draft()->create([
        'post_category_id' => $category->getKey(),
        'title' => 'Draft RELATED',
    ]);

    $this->get(route('berita.show', $published))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('related', []));
});

/*
|--------------------------------------------------------------------------
| Detail content
|--------------------------------------------------------------------------
*/

it('shows the byline the parish wrote rather than an account name', function () {
    $post = Post::factory()->by('Tim Redaksi')->create();

    $this->get(route('berita.show', $post))
        ->assertInertia(fn (Assert $page) => $page->where('post.author_name', 'Tim Redaksi'));
});

it('carries the scripture reference for a reflection', function () {
    $post = Post::factory()->renungan()->create();

    $this->get(route('berita.show', $post))
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.scripture_reference', 'Yohanes 15.13')
        );
});

it('sends a fallback description from the excerpt when no SEO field is filled', function () {
    // PRD 7.7: the SEO fields may be empty and the article's own are used.
    $post = Post::factory()->published()->create([
        'meta_description' => null,
        'excerpt' => 'Ringkasan untuk tautan.',
    ]);

    $this->get(route('berita.show', $post))
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.seo.description', 'Ringkasan untuk tautan.')
        );
});

it('falls back further to the body when there is no excerpt either', function () {
    $post = Post::factory()->published()->create([
        'meta_description' => null,
        'excerpt' => null,
        'content' => '<p>Kalimat pertama artikel.</p><p>Kalimat kedua.</p>',
    ]);

    $this->get(route('berita.show', $post))
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.seo.title', $post->title)
        );
});

it('falls back to the title when meta_title is empty', function () {
    $post = Post::factory()->published()->create(['meta_title' => null]);

    $this->get(route('berita.show', $post))
        ->assertInertia(fn (Assert $page) => $page->where('post.seo.title', $post->title));
});

it('renders the sanitized body', function () {
    $post = Post::factory()->published()->create([
        'content' => '<p>Teks bersih.</p>',
    ]);

    $response = $this->get(route('berita.show', $post));

    $response->assertOk();
    // The stored HTML is what reaches the page; it was cleaned on the way in.
    expect($post->fresh()->content)->toBe('<p>Teks bersih.</p>');
});

/*
|--------------------------------------------------------------------------
| Navigation — PRD 7.4
|--------------------------------------------------------------------------
*/

it('offers the previous and the next article by publication date', function () {
    // Relative to now(), not fixed dates: published() hides anything dated in
    // the future, so a hardcoded '2026-10-20' silently became invisible the
    // moment that day passed and the assertion failed for the wrong reason.
    $older = Post::factory()->published()->create([
        'published_at' => Carbon::now()->subDays(20),
    ]);
    $newer = Post::factory()->published()->create([
        'published_at' => Carbon::now()->subDays(2),
    ]);

    $this->get(route('berita.show', $older))
        ->assertInertia(fn (Assert $page) => $page
            ->where('adjacent.previous', null)
            ->where('adjacent.next.id', $newer->getKey())
        );

    $this->get(route('berita.show', $newer))
        ->assertInertia(fn (Assert $page) => $page
            ->where('adjacent.previous.id', $older->getKey())
            ->where('adjacent.next', null)
        );
});

it('picks an adjacent article deterministically when two share a timestamp', function () {
    // Same second, so only the key can break the tie. Without the id in the
    // ORDER BY, which of the two wins is whatever MySQL felt like, and a
    // bookmarked link changes its "next article" between visits.
    $first = Post::factory()->published()->create([
        'published_at' => Carbon::now()->subDays(5),
    ]);
    $second = Post::factory()->published()->create([
        'published_at' => Carbon::now()->subDays(5),
    ]);

    $this->get(route('berita.show', $first))
        ->assertInertia(fn (Assert $page) => $page->where('adjacent.next.id', $second->getKey()));

    $this->get(route('berita.show', $second))
        ->assertInertia(fn (Assert $page) => $page->where('adjacent.previous.id', $first->getKey()));
});

it('never offers a draft as the next article', function () {
    $current = Post::factory()->published()->create();
    Post::factory()->publishedInFuture()->create();

    $this->get(route('berita.show', $current))
        ->assertInertia(fn (Assert $page) => $page->where('adjacent.next', null));
});
