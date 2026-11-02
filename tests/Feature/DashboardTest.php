<?php

use App\Enums\LivestreamBroadcastStatus;
use App\Models\Backup;
use App\Models\Community;
use App\Models\Document;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Livestream;
use App\Models\MassSchedule;
use App\Models\Media;
use App\Models\Pastor;
use App\Models\Post;
use App\Models\ScheduleNotice;
use App\Models\Service;
use App\Models\SpecialMass;
use App\Models\User;
use App\Services\Backup\BackupHealth;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Phase 9B — the admin dashboard aggregator.
 *
 * Every statistic is asserted against the definition the roadmap fixes, and the
 * cases are chosen so a generic "published" filter, a missing time filter or a
 * wrong date comparison fails the suite rather than passing quietly.
 */

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = superAdmin();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/dashboard'));
});

test('a verified user without admin access is forbidden', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('the dashboard renders on an empty database', function () {
    $user = superAdmin();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('summary.posts.total', 0)
            ->where('summary.posts.published', 0)
            ->where('summary.events.upcoming', 0)
            ->where('summary.documents.expired', 0)
            ->where('summary.massSchedules.regular', 0)
            ->where('summary.services.active', 0)
            ->has('upcomingEvents', 0)
            ->has('recentPosts', 0)
            ->has('attentionItems', 0)
            ->has('recentContent', 0)
        );
});

test('published articles are counted apart from drafts, scheduled and archived', function () {
    $user = superAdmin();

    Post::factory()->count(2)->create(); // published, dated an hour ago
    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();
    Post::factory()->archived()->create();
    Post::factory()->publishedInFuture()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.posts.total', 6)
            ->where('summary.posts.published', 2)
            ->where('summary.posts.draft', 1)
            ->where('summary.posts.scheduled', 1)
            ->where('summary.posts.archived', 1)
        );
});

test('upcoming activities exclude the past, the cancelled and the postponed', function () {
    $user = superAdmin();

    Event::factory()->count(2)->create(); // published, tomorrow
    Event::factory()->past()->create();
    Event::factory()->cancelled()->create();
    Event::factory()->postponed()->create();
    Event::factory()->unpublished()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.events.upcoming', 2)
            ->where('summary.events.draft', 1)
            ->where('summary.events.cancelled', 1)
            ->where('summary.events.postponed', 1)
        );
});

test('expired documents count only a past non-null expiry date', function () {
    $user = superAdmin();

    Document::factory()->count(2)->create(); // draft
    Document::factory()->published()->create();
    Document::factory()->archived()->create();
    Document::factory()->expired()->create(); // draft, expired yesterday
    Document::factory()->published()->create(['expires_at' => null]);
    Document::factory()->published()->create([
        'expires_at' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.documents.total', 7)
            ->where('summary.documents.draft', 3)
            ->where('summary.documents.published', 3)
            ->where('summary.documents.archived', 1)
            ->where('summary.documents.expired', 1)
        );
});

test('the three mass schedule sources are counted apart', function () {
    $user = superAdmin();

    MassSchedule::factory()->count(2)->create(); // active
    MassSchedule::factory()->inactive()->create();
    SpecialMass::factory()->create(); // upcoming
    SpecialMass::factory()->past()->create();
    ScheduleNotice::factory()->create(); // currently visible
    ScheduleNotice::factory()->expired()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.massSchedules.regular', 2)
            ->where('summary.massSchedules.special', 1)
            ->where('summary.massSchedules.notices', 1)
        );
});

test('services are counted by their active flag', function () {
    $user = superAdmin();

    Service::factory()->count(2)->active()->create();
    Service::factory()->create(); // inactive

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.services.active', 2)
            ->where('summary.services.inactive', 1)
        );
});

test('the upcoming and recent lists are bounded', function () {
    $user = superAdmin();

    Event::factory()->count(7)->create();
    Post::factory()->count(5)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('upcomingEvents', 5)
            ->has('recentPosts', 3)
        );
});

test('recent content merges the newest rows across modules', function () {
    $user = superAdmin();

    Post::factory()->count(4)->create();
    Event::factory()->count(4)->create();
    Document::factory()->count(4)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentContent', 8)
            ->has('recentContent.0', fn (Assert $row) => $row->hasAll([
                'type', 'id', 'title', 'status', 'status_label',
                'is_published', 'updated_at',
            ]))
        );
});

test('content without image alt text is reported as an attention item', function () {
    $user = superAdmin();
    $media = Media::factory()->ready()->create();

    Post::factory()->create([
        'media_id' => $media->id,
        'featured_image' => 'media/variants/large/contoh.jpg',
        'image_alt' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('attentionItems', 1)
            ->where('attentionItems.0.key', 'media_alt_text')
            ->where('attentionItems.0.count', 1)
            ->has('attentionItems.0.items', 1)
            ->where('attentionItems.0.items.0.type', 'post')
        );
});

test('a published article with alt text is not reported', function () {
    $user = superAdmin();
    $media = Media::factory()->ready()->create();

    Post::factory()->create([
        'media_id' => $media->id,
        'featured_image' => 'media/variants/large/contoh.jpg',
        'image_alt' => 'Umat berdoa di gereja',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('attentionItems', 0));
});

test('expired documents are reported as an attention item with links', function () {
    $user = superAdmin();

    Document::factory()->expired()->count(2)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attentionItems.0.key', 'expired_documents')
            ->where('attentionItems.0.count', 2)
            ->has('attentionItems.0.items', 2)
            ->where('attentionItems.0.items.0.type', 'document')
        );
});

test('scheduled articles due within a week are reported', function () {
    $user = superAdmin();
    $soon = Post::factory()->scheduled(now()->addDays(3))->create(['title' => 'Segera']);
    Post::factory()->scheduled(now()->addDays(10))->create(['title' => 'Nanti']);
    Post::factory()->scheduled(now()->subDay())->create(['title' => 'Lewat']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attentionItems.0.key', 'scheduled_posts')
            ->where('attentionItems.0.tone', 'info')
            ->where('attentionItems.0.count', 1)
            ->where('attentionItems.0.items.0.id', $soon->getKey())
        );
});

test('drafts untouched for two weeks are reported', function () {
    $user = superAdmin();

    $this->travel(-20)->days();
    Post::factory()->draft()->create(['title' => 'Draf Berita']);
    Event::factory()->unpublished()->create(['title' => 'Draf Agenda']);
    Document::factory()->create(['title' => 'Draf Dokumen']);
    $this->travelBack();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attentionItems.0.key', 'stale_drafts')
            ->where('attentionItems.0.count', 3)
            ->has('attentionItems.0.items', 3)
        );
});

test('unpublished galleries are reported', function () {
    $user = superAdmin();

    GalleryAlbum::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attentionItems.0.key', 'unpublished_galleries')
            ->where('attentionItems.0.tone', 'info')
            ->where('attentionItems.0.count', 2)
            ->has('attentionItems.0.items', 2)
        );
});

test('missing core site identity settings are reported', function () {
    $user = superAdmin();

    app(SiteSettingsService::class)->set('parish_name', 'Paroki Uji');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attentionItems.0.key', 'site_identity')
            ->where('attentionItems.0.count', 4)
            ->where('attentionItems.0.details.0', 'Logo paroki')
            ->where('attentionItems.0.href', route('settings.edit'))
        );
});

test('alt text gaps in communities, pastors and galleries are reported', function () {
    $user = superAdmin();
    $media = Media::factory()->ready()->create();

    Community::factory()->published()->create([
        'logo_media_id' => $media->getKey(),
        'logo_alt' => null,
    ]);
    Pastor::factory()->create([
        'photo_media_id' => $media->getKey(),
        'photo_alt' => null,
    ]);
    $album = GalleryAlbum::factory()->published()->create();
    GalleryPhoto::factory()->create([
        'gallery_album_id' => $album->getKey(),
        'media_id' => $media->getKey(),
        'alt_text' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attentionItems.0.key', 'media_alt_text')
            ->where('attentionItems.0.count', 3)
            ->has('attentionItems.0.items', 3)
            ->where('attentionItems.0.items.0.type', 'community')
            ->where('attentionItems.0.items.1.type', 'pastor')
            ->where('attentionItems.0.items.2.type', 'gallery')
        );
});

test('backup problems are reported to users who can view backups', function () {
    $user = superAdmin();

    Cache::put(BackupHealth::WORKER_HEARTBEAT, now()->toIso8601String(), 86400);
    Cache::put(BackupHealth::SCHEDULER_HEARTBEAT, now()->toIso8601String(), 86400);
    Backup::factory()->failed()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attentionItems.0.key', 'backup_health')
            ->where('attentionItems.0.tone', 'danger')
            ->where('attentionItems.0.details.0', 'Backup terakhir gagal.')
            ->where('attentionItems.0.href', route('backups.index'))
        );
});

test('backup problems are hidden without backups.view', function () {
    seedRolesAndPermissions();
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');

    Backup::factory()->failed()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('attentionItems', 0));
});

test('the next livestream is reported', function () {
    $user = superAdmin();
    $scheduled = Livestream::factory()->published()->create([
        'title' => 'Siaran Besok',
        'broadcast_status' => LivestreamBroadcastStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextLivestream.id', $scheduled->getKey())
            ->where('nextLivestream.title', 'Siaran Besok')
            ->where('nextLivestream.is_live', false)
        );
});

test('a live stream takes precedence over the next scheduled one', function () {
    $user = superAdmin();

    Livestream::factory()->published()->create([
        'broadcast_status' => LivestreamBroadcastStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
    ]);
    $live = Livestream::factory()->live()->create(['title' => 'Sedang Live']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextLivestream.id', $live->getKey())
            ->where('nextLivestream.is_live', true)
        );
});

test('recent content includes galleries and livestreams', function () {
    $user = superAdmin();

    $this->travel(-1)->minute();
    GalleryAlbum::factory()->create(['title' => 'Album Baru']);
    $this->travelBack();
    Livestream::factory()->published()->create(['title' => 'Siaran Baru']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentContent', 2)
            ->where('recentContent.0.type', 'livestream')
            ->where('recentContent.1.type', 'gallery')
        );
});

test('the dashboard does not leak private fields', function () {
    $user = superAdmin();

    Post::factory()->create();
    Event::factory()->create();
    Document::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('recentContent.0.deleted_at')
            ->missing('recentContent.0.created_at')
            ->missing('recentContent.0.uploaded_by')
            ->missing('upcomingEvents.0.deleted_at')
            ->missing('upcomingEvents.0.media_id')
            ->missing('recentPosts.0.deleted_at')
            ->missing('recentPosts.0.media_id')
        );
});
