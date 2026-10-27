<?php

use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Location;
use App\Models\Media;
use App\Models\MediaVariant;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Service;
use App\Models\SpecialMass;
use App\Services\SiteSettingsService;
use App\Support\HomepageIllustration;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| The public homepage — Phase 9A
|--------------------------------------------------------------------------
|
| These are the server-side contracts the page depends on. The page itself can
| only render what the controller proves is public, so the tests here are about
| scopes, limits, toggles, and the shape of the payload — not about markup.
|
| The date boundary for the agenda is the same one /agenda uses, which is why
| the scope lives on the Event model instead of in two controllers.
|
| @see docs/plan-implementation/plan-phase-9a-homepage.md
| @see docs/DECISIONS.md D-70
*/

beforeEach(function () {
    // The settings cache is an array store that outlives RefreshDatabase, so a
    // value written by one test would otherwise be visible to the next.
    app(SiteSettingsService::class)->forget();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('renders the homepage for a guest', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/beranda')
            ->has('hero')
            ->has('sections')
            ->has('news')
            ->has('events')
            ->has('services')
            ->has('galleryPhotos')
        );
});

it('renders an empty homepage without an exception', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextMass', null)
            ->where('news', [])
            ->where('events', [])
            ->where('devotion', null)
            ->where('services', [])
            ->where('galleryPhotos', [])
            ->where('sections.devotion', true)
            ->where('sections.services', true)
            ->where('sections.gallery', true)
        );
});

/*
|--------------------------------------------------------------------------
| Next mass
|--------------------------------------------------------------------------
*/

it('sends the resolver answer as the next mass', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-18 08:00:00', 'Asia/Pontianak'));

    $location = Location::factory()->create(['name' => 'Gereja Paroki']);

    SpecialMass::factory()->create([
        'location_id' => $location->getKey(),
        'title' => 'Misa Syukur',
        'start_at' => Carbon::parse('2026-10-18 09:00:00', 'Asia/Pontianak')->utc(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextMass.kind', 'khusus')
            ->where('nextMass.name', 'Misa Syukur')
            ->where('nextMass.location.name', 'Gereja Paroki')
        );
});

it('sends null when no mass is scheduled', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('nextMass', null));
});

/*
|--------------------------------------------------------------------------
| News
|--------------------------------------------------------------------------
*/

it('sends only published news, newest first, capped by the setting', function () {
    app(SiteSettingsService::class)->set('home_news_limit', 5);

    $newest = Post::factory()->published()->create([
        'title' => 'Terbaru',
        'published_at' => Carbon::now()->subMinute(),
    ]);

    Post::factory()->published()->count(6)->create([
        'published_at' => Carbon::now()->subDay(),
    ]);

    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();
    Post::factory()->publishedInFuture()->create();
    Post::factory()->archived()->create();
    Post::factory()->published()->create()->delete();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('news', 5)
            // D-39: a non-paginated list must not keep the data envelope.
            ->missing('news.data')
            ->where('news.0.id', $newest->getKey())
        );
});

it('does not send the article body in the homepage cards', function () {
    Post::factory()->published()->create([
        'content' => '<p>Rahasia artikel beranda.</p>',
    ]);

    expect($this->get('/')->assertOk()->getContent())
        ->not->toContain('Rahasia artikel beranda');
});

/*
|--------------------------------------------------------------------------
| Agenda
|--------------------------------------------------------------------------
*/

it('sends activities from the start of today in the parish timezone', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-18 08:00:00', 'Asia/Pontianak'));

    $startOfDay = Carbon::parse('2026-10-18 00:00:00', 'Asia/Pontianak');

    $today = Event::factory()->create([
        'title' => 'Kegiatan Hari Ini',
        'start_at' => $startOfDay->copy()->addHours(7)->utc(),
        'end_at' => $startOfDay->copy()->addHours(10)->utc(),
    ]);

    Event::factory()->create([
        'title' => 'Kegiatan Kemarin',
        'start_at' => $startOfDay->copy()->subDay()->addHours(7)->utc(),
    ]);

    Event::factory()->create([
        'title' => 'Kegiatan Besok',
        'start_at' => $startOfDay->copy()->addDay()->addHours(9)->utc(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 2)
            ->missing('events.data')
            ->where('events.0.id', $today->getKey())
        );
});

it('keeps cancelled and postponed activities with their labels', function () {
    Event::factory()->cancelled()->create([
        'title' => 'Batal',
        'start_at' => Carbon::now()->addDay()->utc(),
    ]);
    Event::factory()->postponed()->create([
        'title' => 'Ditunda',
        'start_at' => Carbon::now()->addDays(2)->utc(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.0.status', 'cancelled')
            ->where('events.0.status_label', 'Dibatalkan')
            ->where('events.1.status', 'postponed')
            ->where('events.1.status_label', 'Ditunda')
        );
});

it('caps the agenda by the setting', function () {
    app(SiteSettingsService::class)->set('home_events_limit', 5);

    Event::factory()->count(8)->create([
        'start_at' => Carbon::now()->addDays(10)->utc(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('events', 5));
});

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

it('sends only active services in module order', function () {
    $second = Service::factory()->active()->create([
        'title' => 'Kedua',
        'sort_order' => 2,
    ]);
    $first = Service::factory()->active()->create([
        'title' => 'Pertama',
        'sort_order' => 1,
    ]);
    Service::factory()->create(['title' => 'Tidak Aktif']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('services', 2)
            ->missing('services.data')
            ->where('services.0.id', $first->getKey())
            ->where('services.1.id', $second->getKey())
        );
});

/*
|--------------------------------------------------------------------------
| Gallery
|--------------------------------------------------------------------------
*/

it('sends only ready photos from published albums, capped by the setting', function () {
    app(SiteSettingsService::class)->set('home_gallery_limit', 2);

    $album = GalleryAlbum::factory()->published()->create();
    $draft = GalleryAlbum::factory()->create();

    GalleryPhoto::factory()->count(3)->create([
        'gallery_album_id' => $album->getKey(),
    ]);

    GalleryPhoto::factory()->create([
        'gallery_album_id' => $draft->getKey(),
    ]);

    GalleryPhoto::factory()->create([
        'gallery_album_id' => $album->getKey(),
        'media_id' => Media::factory()->create()->getKey(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('galleryPhotos', 2)
            ->missing('galleryPhotos.data')
            ->where('galleryPhotos.0.album.slug', $album->slug)
        );
});

/*
|--------------------------------------------------------------------------
| Devotion
|--------------------------------------------------------------------------
*/

it('sends the newest reflection from the Renungan category', function () {
    $renungan = PostCategory::factory()->create([
        'name' => 'Renungan',
        'slug' => 'renungan',
    ]);
    $berita = PostCategory::factory()->create(['name' => 'Berita', 'slug' => 'berita']);

    $reflection = Post::factory()->published()->create([
        'post_category_id' => $renungan->getKey(),
        'published_at' => Carbon::now()->subDay(),
    ]);

    Post::factory()->published()->create([
        'post_category_id' => $berita->getKey(),
        'published_at' => Carbon::now(),
    ]);

    Post::factory()->draft()->create([
        'post_category_id' => $renungan->getKey(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('devotion.id', $reflection->getKey())
            ->where('devotion.category.slug', 'renungan')
        );
});

it('hides the reflection when its toggle is off', function () {
    $renungan = PostCategory::factory()->create([
        'name' => 'Renungan',
        'slug' => 'renungan',
    ]);
    Post::factory()->published()->create([
        'post_category_id' => $renungan->getKey(),
    ]);

    app(SiteSettingsService::class)->set('home_show_devotion', false);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('devotion', null)
            ->where('sections.devotion', false)
        );
});

/*
|--------------------------------------------------------------------------
| Toggles and contact
|--------------------------------------------------------------------------
*/

it('does not query or send services and gallery when their toggles are off', function () {
    Service::factory()->active()->create();
    $album = GalleryAlbum::factory()->published()->create();
    GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);

    app(SiteSettingsService::class)->set('home_show_services', false);
    app(SiteSettingsService::class)->set('home_show_gallery', false);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('services', [])
            ->where('galleryPhotos', [])
            ->where('sections.services', false)
            ->where('sections.gallery', false)
        );
});

it('keeps the contact page independent of the homepage contact toggle', function () {
    app(SiteSettingsService::class)->set('home_show_contact', false);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.homeShowContact', false)
        );

    $this->get('/kontak')->assertOk();
});

/*
|--------------------------------------------------------------------------
| Hero
|--------------------------------------------------------------------------
*/

it('sends the configured hero text and media, with a fallback image', function () {
    $media = Media::factory()->ready()->create([
        'width' => 1600,
        'height' => 900,
    ]);
    MediaVariant::factory()->create([
        'media_id' => $media->getKey(),
        'name' => 'large',
    ]);

    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Sambut umat di rumah',
        'short_description' => 'Jadwal misa, kabar, dan pelayanan paroki.',
        'home_hero_image' => (string) $media->getKey(),
        'home_hero_image_alt' => 'Gereja paroki pada malam hari',
        'motto_verse' => 'Sebab di mana dua atau tiga orang berkumpul dalam nama-Ku, di situ Aku ada.',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('hero.title', 'Sambut umat di rumah')
            ->where('hero.description', 'Jadwal misa, kabar, dan pelayanan paroki.')
            ->where('hero.image', fn (string $url): bool => str_contains($url, 'storage'))
            ->where('hero.imageAlt', 'Gereja paroki pada malam hari')
            ->where('hero.width', 1600)
            ->where('hero.height', 900)
            ->where('hero.motto', 'Sebab di mana dua atau tiga orang berkumpul dalam nama-Ku, di situ Aku ada.')
        );
});

it('uses the static hero fallback when the parish has not uploaded one', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('hero.title', null)
            ->where('hero.description', null)
            ->where('hero.image', '/hero-section.webp')
            ->where('hero.imageAlt', 'Foto gereja paroki pada malam hari')
            ->where('hero.ogImage', '/hero-section.png')
            ->where('hero.width', 1672)
            ->where('hero.height', 941)
        );
});

it('does not send internal columns on the homepage', function () {
    Post::factory()->published()->create();
    Event::factory()->create();
    Service::factory()->active()->create();
    $album = GalleryAlbum::factory()->published()->create();
    GalleryPhoto::factory()->create(['gallery_album_id' => $album->getKey()]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('news.0.content')
            ->missing('news.0.deleted_at')
            ->missing('events.0.deleted_at')
            ->missing('services.0.is_active')
            ->missing('galleryPhotos.0.media_id')
        );
});

/*
|--------------------------------------------------------------------------
| SEO fallbacks in the initial HTML
|--------------------------------------------------------------------------
*/

it('renders the hero image as the homepage Open Graph fallback', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('property="og:image"')
        ->toContain('hero-section.png');
});

it('uses the short description as the homepage meta description fallback', function () {
    app(SiteSettingsService::class)->set(
        'short_description',
        'Komunitas umat Katolik di Putussibau.',
    );

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('name="description"')
        ->toContain('Komunitas umat Katolik di Putussibau.');
});

/*
|--------------------------------------------------------------------------
| Illustrations (D-71)
|--------------------------------------------------------------------------
|
| These are fallback-only demo images. They are off unless the flag is set, and
| they never replace a real upload.
|
*/

it('keeps illustrations off by default', function () {
    Post::factory()->published()->create([
        'title' => 'Tanpa Gambar',
        'featured_image' => null,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('news.0.image', null)
            ->where('galleryIllustrations', [])
        );
});

it('fills an empty news image with a labeled illustration when enabled', function () {
    config(['homepage.illustrations.enabled' => true]);

    Post::factory()->published()->create([
        'title' => 'Tanpa Gambar',
        'featured_image' => null,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('news.0.image_is_illustration', true)
            ->where('news.0.image', fn (string $url): bool => str_contains($url, 'illustrations/'))
            ->where('news.0.image_alt', fn (string $alt): bool => str_starts_with($alt, 'Ilustrasi:'))
            ->has('news.0.image_srcset')
        );
});

it('never replaces a real news image with an illustration', function () {
    config(['homepage.illustrations.enabled' => true]);

    Post::factory()->published()->create([
        'title' => 'Punya Gambar',
        'featured_image' => 'news/real.webp',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('news.0.image', fn (string $url): bool => str_contains($url, 'real.webp'))
            ->missing('news.0.image_is_illustration')
        );
});

it('fills a service thumbnail with an illustration when enabled', function () {
    config(['homepage.illustrations.enabled' => true]);

    Service::factory()->active()->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('services.0.thumbnail_is_illustration', true)
            ->where('services.0.thumbnail', fn (string $url): bool => str_contains($url, 'illustrations/'))
        );
});

it('sends a separate labeled illustration strip for an empty gallery', function () {
    config(['homepage.illustrations.enabled' => true]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('galleryPhotos', [])
            ->has('galleryIllustrations', 6)
            ->where('galleryIllustrations.0.alt', fn (string $alt): bool => str_starts_with($alt, 'Ilustrasi:'))
        );
});

it('picks the same illustration for the same seed', function () {
    config(['homepage.illustrations.enabled' => true]);

    expect(HomepageIllustration::for('post-42'))->toBe(HomepageIllustration::for('post-42'));
});
