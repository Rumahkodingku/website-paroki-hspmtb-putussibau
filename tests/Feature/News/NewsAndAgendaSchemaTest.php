<?php

use App\Enums\EventStatus;
use App\Enums\PostStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Location;
use App\Models\Post;
use App\Models\PostCategory;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\PostCategorySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| News & Agenda — schema and domain
|--------------------------------------------------------------------------
|
| The four tables Phase 05 adds, and the four models over them. Most of what is
| here is not about Eloquent working; it is about the shape of the schema being
| the shape Phase 05 decided on, because those are the decisions that a later
| phase could quietly undo by adding a column.
|
| The two that matter most:
|
|   - there is no community_id, and nothing in this module points at
|     communities. Roadmap section 5 rules it out and closes by saying the PRD
|     is to be reconciled separately (D-43). A test is what stops "let's just
|     add it in Phase 07" from being the first anybody notices.
|
|   - a soft-deleted article keeps its slug, on purpose (D-45). That is the
|     opposite of `locations.slug`, which releases its slug when deleted, and
|     the reason is route model binding: two rows answering one slug would make
|     the public binding ambiguous.
|
| @see docs/DECISIONS.md D-43, D-45
*/

/*
|--------------------------------------------------------------------------
| Schema
|--------------------------------------------------------------------------
*/

it('creates the four tables', function (string $table) {
    expect(Schema::hasTable($table))->toBeTrue();
})->with(['post_categories', 'posts', 'event_categories', 'events']);

it('has no community_id on either content table', function (string $table) {
    // Roadmap 5. The `communities` table does not exist yet either; it arrives
    // with Phase 07, and it is Phase 07's job to decide how to link back.
    expect(Schema::hasColumn($table, 'community_id'))->toBeFalse();
})->with(['posts', 'events']);

it('has no community foreign key on either content table', function (string $table) {
    $foreignTables = collect(Schema::getForeignKeys($table))
        ->pluck('foreign_table')
        ->all();

    expect($foreignTables)->not->toContain('communities');
})->with(['posts', 'events']);

it('keeps a slug unique across soft-deleted rows', function () {
    $category = PostCategory::factory()->create();
    $post = Post::factory()->create(['post_category_id' => $category->getKey()]);

    $post->delete();

    expect(Schema::hasColumn('posts', 'active_marker'))->toBeFalse('posts must not grow the mass_schedules generated column');

    // The point of the decision: the slug is still held.
    expect(Post::withTrashed()->where('slug', $post->slug)->count())->toBe(1);

    expect(fn () => Post::factory()->create([
        'post_category_id' => $category->getKey(),
        'slug' => $post->slug,
    ]))->toThrow(QueryException::class);
});

it('refuses to delete a category that is still in use', function (string $relation) {
    $category = $relation === 'posts'
        ? PostCategory::factory()->create()
        : EventCategory::factory()->create();

    $relation === 'posts'
        ? Post::factory()->create(['post_category_id' => $category->getKey()])
        : Event::factory()->create(['event_category_id' => $category->getKey()]);

    // RESTRICT at the database. The controller turns this into a sentence; this
    // is the layer underneath it, and it is what holds if the sentence is ever
    // removed.
    expect(fn () => $category->delete())->toThrow(QueryException::class);
})->with(['posts', 'events']);

it('keeps an activity readable after its master location is soft deleted', function () {
    $location = Location::factory()->create(['name' => 'Stasi Tanah Empang']);
    $event = Event::factory()->create([
        'location_id' => $location->getKey(),
        'location_name' => 'Lapangan Santo Yosef',
    ]);

    // Phase 04 deletes locations with a soft delete, so ON DELETE SET NULL does
    // not fire here — the column keeps its value and the relation simply
    // resolves to nothing. What matters to a visitor is that the activity is
    // still listed and still says where it is, which is location_name.
    $location->delete();
    $event->refresh();

    expect($event->location)->toBeNull()
        ->and($event->displayLocationName())->toBe('Lapangan Santo Yosef');
});

it('nulls the location reference when the row itself is deleted', function () {
    // The database rule underneath the soft delete above. Nothing in the
    // interface forces a delete, so this is asserted directly against the
    // schema rather than through a request.
    $location = Location::factory()->create();
    $event = Event::factory()->create([
        'location_id' => $location->getKey(),
        'location_name' => 'Lapangan Santo Yosef',
    ]);

    $location->forceDelete();
    $event->refresh();

    expect($event->location_id)->toBeNull()
        ->and($event->location_name)->toBe('Lapangan Santo Yosef');
});

/*
|--------------------------------------------------------------------------
| Post status
|--------------------------------------------------------------------------
*/

it('defaults an article to draft with no publication date', function () {
    $post = Post::factory()->draft()->create();

    expect($post->status)->toBe(PostStatus::Draft)
        ->and($post->published_at)->toBeNull();
});

it('publishes only articles that are published and dated in the past', function () {
    $published = Post::factory()->published()->create();
    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();
    Post::factory()->publishedInFuture()->create();
    Post::factory()->archived()->create();
    Post::factory()->published()->create()->delete();

    expect(Post::published()->pluck('id')->all())->toBe([$published->getKey()]);
});

it('keeps a soft-deleted article out of the published scope', function () {
    $post = Post::factory()->published()->create();
    $post->delete();

    expect(Post::published()->count())->toBe(0);
});

it('reads a publication date as UTC and renders it in WIB', function () {
    $post = Post::factory()->published()->create([
        'published_at' => Carbon::parse('2026-10-11 02:00:00', 'UTC'),
    ]);

    expect($post->published_at->utc()->toIso8601String())->toBe('2026-10-11T02:00:00+00:00')
        // 02:00 UTC is nine in the morning in Pontianak.
        ->and($post->published_at->setTimezone(config('app.display_timezone'))->format('H:i'))->toBe('09:00');
});

it('falls back to the account name when no byline was written', function () {
    $post = Post::factory()->create(['author_name' => null]);

    expect($post->displayAuthorName())->toBe($post->author->name);
});

it('prefers a byline that was written out', function () {
    expect(Post::factory()->by('Tim Redaksi')->create()->displayAuthorName())->toBe('Tim Redaksi');
});

it('falls back from the seo fields to the article itself', function () {
    $post = Post::factory()->create([
        'meta_title' => null,
        'meta_description' => null,
        'excerpt' => 'Ringkasan artikel.',
    ]);

    expect($post->displayMetaTitle())->toBe($post->title)
        ->and($post->displayDescription())->toBe('Ringkasan artikel.');
});

it('turns the body into plain text when there is no excerpt', function () {
    $post = Post::factory()->create([
        'meta_title' => 'Judul SEO',
        'meta_description' => null,
        'excerpt' => null,
        'content' => '<p>Kata pertama.</p><p>Kata kedua.</p>',
    ]);

    expect($post->displayMetaTitle())->toBe('Judul SEO')
        ->and($post->displayDescription())->toContain('Kata pertama.')
        // The markup must not survive into a meta tag.
        ->and($post->displayDescription())->not->toContain('<p>');
});

/*
|--------------------------------------------------------------------------
| Event status
|--------------------------------------------------------------------------
*/

it('shows a published activity regardless of when it is', function () {
    Event::factory()->create();
    Event::factory()->past()->create();
    Event::factory()->cancelled()->create();
    Event::factory()->postponed()->create();
    Event::factory()->unpublished()->create();
    Event::factory()->create()->delete();

    expect(Event::published()->count())->toBe(4);
});

it('reads an activity time as UTC', function () {
    // 09:00 WIB on the 12th is 02:00 UTC on the 12th.
    $event = Event::factory()->create([
        'start_at' => Carbon::parse('2026-10-12 09:00:00', config('app.display_timezone'))->utc(),
    ]);

    expect($event->start_at->utc()->toIso8601String())->toBe('2026-10-12T02:00:00+00:00');
});

it('prefers the master location over the free text', function () {
    $location = Location::factory()->create(['name' => 'Gereja Paroki']);
    $event = Event::factory()->create([
        'location_id' => $location->getKey(),
        'location_name' => 'Lapangan Santo Yosef',
    ]);

    expect($event->displayLocationName())->toBe('Gereja Paroki');
});

it('falls back to the free text when there is no master location', function () {
    $event = Event::factory()->create(['location_id' => null]);

    expect($event->displayLocationName())->toBe($event->location_name);
});

it('reports no location when neither is filled', function () {
    expect(Event::factory()->create([
        'location_id' => null,
        'location_name' => '   ',
    ])->displayLocationName())->toBeNull();
});

it('reduces a rich description to text for the calendar file', function () {
    $event = Event::factory()->create(['description' => '<p>Rapat <strong>OMK</strong>.</p>']);

    expect($event->plainDescription())->toBe('Rapat OMK.');
});

/*
|--------------------------------------------------------------------------
| Seeder
|--------------------------------------------------------------------------
*/

it('seeds the four categories PRD 9.6 names, in order', function () {
    $this->seed(PostCategorySeeder::class);

    expect(PostCategory::query()->orderBy('sort_order')->pluck('slug')->all())
        ->toBe(['berita', 'kegiatan', 'renungan', 'pengumuman']);
});

it('seeds the five activity categories PRD 9.7 names, with a valid hex each', function () {
    $this->seed(EventCategorySeeder::class);

    $categories = EventCategory::query()->orderBy('id')->get();

    expect($categories)->toHaveCount(5)
        ->and($categories->pluck('slug')->all())->toBe(['liturgi', 'pembinaan', 'sosial', 'rapat', 'lainnya']);

    foreach ($categories as $category) {
        expect($category->color)->toMatch('/^#[0-9A-F]{6}$/')
            ->and($category->colorValue())->toBe($category->color);
    }
});

it('leaves no content behind after seeding', function () {
    // The base chain seeds only reference data (categories): a bare
    // `migrate:fresh --seed` leaves no article or activity behind. Demo content
    // is a separate, flag-guarded seeder (D-53) and is turned off here so this
    // still asserts the base chain.
    config(['demo.enabled' => false]);

    $this->seed();

    expect(Post::count())->toBe(0)
        ->and(Event::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Route binding
|--------------------------------------------------------------------------
*/

it('resolves a public slug to a published article and refuses the rest', function () {
    $published = Post::factory()->published()->create();
    $draft = Post::factory()->draft()->create();
    $future = Post::factory()->publishedInFuture()->create();

    $model = new Post;

    expect($model->resolveRouteBinding($published->slug, 'slug'))->not->toBeNull()
        ->and($model->resolveRouteBinding($draft->slug, 'slug'))->toBeNull()
        ->and($model->resolveRouteBinding($future->slug, 'slug'))->toBeNull();
});

it('still binds an article by id for the admin routes', function () {
    $draft = Post::factory()->draft()->create();

    // The admin has to reach drafts and archived articles. The public slug
    // binding must not have narrowed the default behaviour.
    expect((new Post)->resolveRouteBinding($draft->getKey()))->not->toBeNull();
});

it('resolves a public slug to a published activity and refuses the rest', function () {
    $published = Event::factory()->create();
    $hidden = Event::factory()->unpublished()->create();

    $model = new Event;

    expect($model->resolveRouteBinding($published->slug, 'slug'))->not->toBeNull()
        ->and($model->resolveRouteBinding($hidden->slug, 'slug'))->toBeNull()
        ->and($model->resolveRouteBinding($published->getKey()))->not->toBeNull();
});

it('labels every status through the language file', function () {
    foreach (PostStatus::cases() as $status) {
        expect($status->label())->not->toBe("news.statuses.{$status->value}");
    }

    foreach (EventStatus::cases() as $status) {
        expect($status->label())->not->toBe("agenda.statuses.{$status->value}");
    }
});
