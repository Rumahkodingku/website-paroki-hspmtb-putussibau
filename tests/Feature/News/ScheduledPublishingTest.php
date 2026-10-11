<?php

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled publishing — PRD AC-POST-6, AC-POST-7
|--------------------------------------------------------------------------
|
| These two acceptance criteria are not really about the command; they are
| about what happens when the command does not run, which is the situation a
| parish site is actually in when the shared host's cron is misconfigured.
|
| So the file spends most of its weight on three properties:
|
|   - an article is public if and only if both status and date say so, so a
|     failed run delays publication rather than causing or preventing it;
|   - the command cannot publish twice, however many times it is invoked;
|   - it never moves published_at, because that column is the author's
|     decision and the article's position in the list.
|
| @see app/Console/Commands/PublishScheduledPosts.php
*/

beforeEach(function () {
    $this->artisan('posts:publish-scheduled');
});

/*
|--------------------------------------------------------------------------
| It publishes what is due
|--------------------------------------------------------------------------
*/

it('publishes an article whose scheduled time has passed', function () {
    $post = Post::factory()->scheduled(Carbon::now()->subMinute())->create();

    $this->artisan('posts:publish-scheduled')
        ->assertSuccessful();

    expect($post->fresh()->status)->toBe(PostStatus::Published);
});

it('publishes every article that is due, not just the first', function () {
    $posts = Post::factory()->count(3)->scheduled(Carbon::now()->subHour())->create();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect(Post::query()->where('status', PostStatus::Published->value)->count())
        ->toBe($posts->count());
});

it('publishes an article whose time has just passed, to the second', function () {
    // The boundary is inclusive. "<=" and "<" differ by exactly one second of
    // an article's visibility per tick, and the error is invisible: it shows up
    // as an article that is simply always a minute late.
    $post = Post::factory()->scheduled(Carbon::now())->create();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->status)->toBe(PostStatus::Published);
});

/*
|--------------------------------------------------------------------------
| It publishes nothing else
|--------------------------------------------------------------------------
*/

it('leaves an article scheduled for later alone', function () {
    $post = Post::factory()->scheduled(Carbon::now()->addHour())->create();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->status)->toBe(PostStatus::Scheduled);
});

it('leaves a draft alone even when its date has passed', function () {
    // The one that matters. A draft with a past date is an editor who picked
    // today while drafting it and has not finished. Publishing it would put
    // unfinished writing on the parish website because a cron ran.
    $post = Post::factory()->create([
        'status' => PostStatus::Draft,
        'published_at' => Carbon::now()->subWeek(),
    ]);

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->status)->toBe(PostStatus::Draft);
});

it('leaves an archived article alone', function () {
    // Archived is where a published article goes when it is taken down. Its
    // published_at is in the past, so a command that filtered on the date alone
    // would resurrect every archived article on the site.
    $post = Post::factory()->archived()->create();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->status)->toBe(PostStatus::Archived);
});

it('leaves a soft-deleted article alone', function () {
    $post = Post::factory()->scheduled(Carbon::now()->subMinute())->create();
    $post->delete();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->status)->toBe(PostStatus::Scheduled);
});

it('leaves a scheduled article with no date alone', function () {
    // The column is nullable and the database allows NULL, so the query has to
    // exclude it explicitly rather than relying on the comparison: NULL <= now
    // is NULL, which is falsy, and it happens to work — but only by accident
    // of three-valued logic, and the assertion is here so it stays deliberate.
    $post = Post::factory()->create([
        'status' => PostStatus::Scheduled,
        'published_at' => null,
    ]);

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->status)->toBe(PostStatus::Scheduled);
});

/*
|--------------------------------------------------------------------------
| Idempotence — AC-POST-7
|--------------------------------------------------------------------------
*/

it('publishes nothing on a second run', function () {
    Post::factory()->count(2)->scheduled(Carbon::now()->subMinute())->create();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();
    $this->artisan('posts:publish-scheduled')
        ->expectsOutputToContain('Tidak ada artikel terjadwal')
        ->assertSuccessful();

    expect(Post::query()->where('status', PostStatus::Published->value)->count())->toBe(2);
});

it('survives being run five times over', function () {
    $post = Post::factory()->scheduled(Carbon::now()->subMinute())->create();

    foreach (range(1, 5) as $ignored) {
        $this->artisan('posts:publish-scheduled')->assertSuccessful();
    }

    // Still one article, still published once. There is no counter that could
    // have been incremented twice here — status is the only thing that moved.
    expect($post->fresh()->status)->toBe(PostStatus::Published)
        ->and(Post::query()->count())->toBe(1);
});

it('reports how many it published', function () {
    Post::factory()->count(3)->scheduled(Carbon::now()->subMinute())->create();

    $this->artisan('posts:publish-scheduled')
        ->expectsOutputToContain('3 artikel terjadwal berhasil diterbitkan')
        ->assertSuccessful();
});

/*
|--------------------------------------------------------------------------
| It moves status and nothing else
|--------------------------------------------------------------------------
*/

it('never changes published_at', function () {
    // The column is the author's chosen moment and it decides the article's
    // position in the list. Overwriting it with now() would silently reorder
    // the front page every time the scheduler ran.
    $moment = Carbon::now()->subDays(3)->startOfSecond();
    $post = Post::factory()->scheduled($moment)->create();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->published_at->equalTo($moment))->toBeTrue();
});

it('never touches updated_at, so the edit history stays honest', function () {
    // updated_at on a published article is what tells an editor when somebody
    // last worked on it. A bulk UPDATE moves it, and moving it would make every
    // scheduled article look hand-edited at its publication minute.
    $post = Post::factory()->scheduled(Carbon::now()->subMinute())->create();
    $before = $post->updated_at;

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->updated_at->equalTo($before))->toBeTrue();
});

it('leaves the body and the slug untouched', function () {
    $post = Post::factory()->scheduled(Carbon::now()->subMinute())->create([
        'title' => 'Slug tetap sama',
    ]);
    $slug = $post->slug;

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->slug)->toBe($slug)
        ->and($post->fresh()->title)->toBe('Slug tetap sama');
});

/*
|--------------------------------------------------------------------------
| What the visitor sees
|--------------------------------------------------------------------------
*/

it('makes a newly published article visible on the public page at once', function () {
    $post = Post::factory()->scheduled(Carbon::now()->subMinute())->create();

    // Before: 404. This is the whole acceptance criterion — the moment the
    // command runs, the article is public, with nothing else to trigger.
    $this->get(route('berita.show', $post))->assertNotFound();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    $this->get(route('berita.show', $post))->assertOk();
});

it('puts a newly published article in the list at its original date', function () {
    // Second newest, not first. The date the author gave decides the position,
    // so a scheduler that rewrote published_at with now() would reshuffle the
    // front page on every run and push a week-old article to the top.
    $tenDaysAgo = Post::factory()->published()->create([
        'published_at' => Carbon::now()->subDays(10),
    ]);
    $fiveDaysAgo = Post::factory()->scheduled(Carbon::now()->subDays(5))->create();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    $this->get('/berita')
        ->assertInertia(fn ($page) => $page
            // Descending by published_at: five days ago is more recent than ten.
            ->where('posts.data.0.id', $fiveDaysAgo->getKey())
            ->where('posts.data.1.id', $tenDaysAgo->getKey())
        );
});

it('counts no views for an article that was not public yet', function () {
    // The counter is not the scheduler's business, but a request during the
    // window between the scheduled time and the tick must leave no trace.
    $post = Post::factory()->scheduled(Carbon::now()->subMinute())->create();

    $this->get(route('berita.show', $post))->assertNotFound();

    expect($post->fresh()->view_count)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| The schedule entry
|--------------------------------------------------------------------------
*/

it('runs every minute', function () {
    // Every minute, and not "hourly on the hour". An article dated 08:00 has to
    // be public at 08:00, which an hourly schedule can be up to 59 minutes
    // late — late enough that the parish notices, and that is the only thing
    // the schedule is for.
    $events = collect(Schedule::events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'posts:publish-scheduled'));

    expect($events)->toHaveCount(1);

    $expression = $events->first()->expression;

    expect($expression)->toContain('* * * * *');
});

it('does not let two runs overlap', function () {
    $event = collect(Schedule::events())
        ->first(fn ($e) => str_contains($e->command ?? '', 'posts:publish-scheduled'));

    expect($event->withoutOverlapping)->toBeTrue();
});

it('still schedules the queue housekeeping', function () {
    // Adding a task must not have replaced the one that was there.
    $commands = collect(Schedule::events())->map(fn ($event) => $event->command ?? '');

    expect($commands->filter(fn ($c) => str_contains($c, 'queue:prune-failed')))->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| It reaches the console
|--------------------------------------------------------------------------
*/

it('is registered as an artisan command', function () {
    $commands = array_keys(Artisan::all());

    expect($commands)->toContain('posts:publish-scheduled');
});

it('needs no administrator to run', function () {
    // The cron line runs it unauthenticated, so a permission gate here would
    // make the command fail silently in production and nowhere else.
    expect(Post::factory()->scheduled(Carbon::now()->subMinute())->create()->exists)->toBeTrue();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();
});
