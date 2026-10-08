<?php

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Infrastructure foundation
|--------------------------------------------------------------------------
|
| Phase 01 section 33 P12. T26, T27 and T28 have their own files; this one
| covers the wiring that has no page to render and therefore no other place
| anybody would look.
|
| Everything asserted here is invisible in a browser, which is why it is worth
| asserting at all: a scheduler that is never started, a dev process that is
| never registered, and a shared prop nothing reads all produce a working
| application and a failed feature.
|
| @see docs/DECISIONS.md D-26
|
*/

beforeEach(function () {
    Cache::forget((string) config('site-settings.cache.key'));
});

/*
|--------------------------------------------------------------------------
| The scheduler
|--------------------------------------------------------------------------
*/

/*
 * The schedule is read through schedule:list rather than by resolving
 * Illuminate\Console\Scheduling\Schedule directly.
 *
 * ApplicationBuilder::withSchedule() registers its callback through
 * Artisan::starting(), so a schedule resolved outside a console run has no
 * events on it even though the schedule itself is correctly configured. The
 * command output is also the thing an operator actually reads when asking what
 * this application is going to do on its own, so it is the better assertion.
 */
function scheduleListing(): string
{
    Artisan::call('schedule:list');

    return Artisan::output();
}

test('the schedule has at least one task', function () {
    // An empty withSchedule() satisfies the word "scheduler" in a checklist
    // while nothing is actually scheduled.
    expect(trim(scheduleListing()))->not->toBe('');
});

test('failed job records are pruned daily', function () {
    expect(scheduleListing())->toContain('0 0 * * *')
        ->toContain('queue:prune-failed');
});

test('the schedule does not invoke the scheduler from inside itself', function () {
    // A cron line runs schedule:run every minute in production and
    // schedule:work runs it in development. Having schedule:run as a schedule
    // task would make it call itself.
    expect(scheduleListing())->not->toContain('schedule:run')
        ->not->toContain('schedule:work');
});

test('no business command is scheduled during phase 01', function () {
    // Roadmap section 25 defers PublishScheduledPosts to Phase 02 and says so
    // explicitly.
    expect(scheduleListing())->not->toContain('posts:publish')
        ->not->toContain('PublishScheduledPosts');
});

test('schedule:list reports the scheduled task', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('queue:prune-failed');
});

/*
|--------------------------------------------------------------------------
| Developer processes
|--------------------------------------------------------------------------
*/

test('composer dev starts the scheduler as well as the queue', function () {
    // Laravel's dev command registers server, queue, pail and vite. The
    // scheduler is not among them, so without an explicit registration a
    // scheduled task never fires in development and nothing says why.
    Artisan::call('dev:list');

    $output = Artisan::output();

    expect($output)->toContain('scheduler')->toContain('schedule:work')
        ->and($output)->toContain('queue')
        ->and($output)->toContain('server')
        ->and($output)->toContain('vite');
});

test('the starter inspire command is gone', function () {
    // It came from the Laravel skeleton, is referenced by nothing, and left the
    // project looking as though it had a console surface it does not have.
    expect(array_keys(Artisan::all()))->not->toContain('inspire');
});

/*
|--------------------------------------------------------------------------
| The SEO shared prop
|--------------------------------------------------------------------------
*/

test('the seo shared prop is present on every page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('seo')
            ->has('seo.appUrl')
            ->has('seo.siteName')
            ->has('seo.title')
            ->has('seo.description')
            ->has('seo.ogImage')
        );
});

test('the seo defaults are read from the site settings', function () {
    // The three seo_default_* keys were configurable and read by nothing, so
    // editing them changed no page. This is the assertion that they now have an
    // effect.
    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Paroki Contoh',
        'seo_default_title' => 'Judul Contoh',
        'seo_default_description' => 'Deskripsi contoh.',
        'seo_default_og_image' => '/storage/media/variants/contoh.webp',
    ]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.siteName', 'Paroki Contoh')
            ->where('seo.title', 'Judul Contoh')
            ->where('seo.description', 'Deskripsi contoh.')
            ->where('seo.ogImage', '/storage/media/variants/contoh.webp')
        );
});

test('an unconfigured setting is null rather than an empty string', function () {
    // The Seo component omits a tag it has no value for, and it can only tell
    // the difference between "never set" and "deliberately blank" if the prop
    // preserves it.
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', null)
            ->where('seo.description', null)
            ->where('seo.ogImage', null)
        );
});

test('a whitespace only setting counts as unconfigured', function () {
    SiteSetting::factory()->keyed('seo_default_description', '   ')->create();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.description', null)
        );
});

test('the seo prop follows a settings save', function () {
    // Same reason T18 exists for the service: the shared prop is a closure, so
    // it is re-read per request and has to reflect the new value immediately
    // rather than a cached map from before the save.
    $admin = superAdmin();

    $this->actingAs($admin)->put(route('settings.update'), [
        'settings' => ['seo_default_title' => 'Judul Baru'],
    ])->assertRedirect(route('settings.edit'));

    $this->actingAs($admin)->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Judul Baru')
        );
});

test('the app url has no trailing slash', function () {
    // The Seo component concatenates appUrl with a path. A trailing slash would
    // produce a doubled one in every og:image and og:url.
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.appUrl', rtrim((string) config('app.url'), '/'))
        );
});

/*
|--------------------------------------------------------------------------
| The server-rendered baseline head
|--------------------------------------------------------------------------
|
| PRD NFR-SEO asks for Open Graph tags in the first HTML response when SSR is
| not enabled, and SSR is not enabled. React renders after hydration, so on its
| own it would be invisible to anything reading the response body.
|
*/

test('open graph tags are present in the initial html response', function () {
    // Nothing is seeded into a refreshed database, so the site name has to be
    // set for the tags that depend on it to have a value.
    app(SiteSettingsService::class)->setMany(['parish_name' => 'Paroki Contoh']);

    // No JavaScript runs during this test, so anything found here came from
    // Blade.
    $html = $this->get(route('home'))->getContent();

    expect($html)->toContain('property="og:site_name"')
        ->toContain('property="og:type"')
        ->toContain('property="og:url"')
        ->toContain('rel="canonical"')
        ->toContain('name="twitter:card"');
});

test('the baseline head tags carry the attribute inertia manages them by', function () {
    // Inertia only manages head elements that carry data-inertia, and it
    // matches a server tag to a client tag by value. Without these the React
    // Seo component would stack a second copy instead of replacing them.
    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Paroki Contoh',
        'seo_default_title' => 'Judul Contoh',
        'seo_default_description' => 'Deskripsi contoh.',
        'seo_default_og_image' => '/storage/media/variants/contoh.webp',
    ]);

    $html = $this->get(route('home'))->getContent();

    // One list, and it has to be the same list in both files. These strings are
    // the contract between resources/views/app.blade.php and
    // resources/js/components/seo.tsx.
    foreach ([
        'og-site-name', 'og-title', 'og-type', 'og-url', 'canonical',
        'description', 'og-description', 'twitter-description',
        'og-image', 'twitter-image', 'twitter-card',
    ] as $key) {
        expect($html)->toContain('data-inertia="'.$key.'"');
    }
});

test('the baseline description and image appear when configured', function () {
    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Paroki Contoh',
        'seo_default_description' => 'Deskripsi contoh.',
        'seo_default_og_image' => '/storage/media/variants/contoh.webp',
    ]);

    $html = $this->get(route('home'))->getContent();

    expect($html)->toContain('content="Deskripsi contoh."')
        // Relative in the setting, absolute in the document. A link unfurler
        // ignores a relative og:image and shows a preview with no picture.
        ->toContain('content="'.rtrim((string) config('app.url'), '/').'/storage/media/variants/contoh.webp"');
});

test('an unconfigured description produces no meta description at all', function () {
    // An empty description is worse than none: crawlers and unfurlers both
    // treat it as a description.
    $html = $this->get(route('home'))->getContent();

    expect($html)->not->toContain('name="description"')
        ->not->toContain('property="og:description"');
});

test('an absolute image setting is not rewritten', function () {
    app(SiteSettingsService::class)->setMany([
        'seo_default_og_image' => 'https://cdn.contoh.test/gambar.png',
    ]);

    $html = $this->get(route('home'))->getContent();

    expect($html)->toContain('content="https://cdn.contoh.test/gambar.png"')
        ->not->toContain('https://localhost/https://');
});
