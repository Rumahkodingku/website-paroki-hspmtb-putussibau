<?php

use App\Models\Document;
use App\Models\Event;
use App\Models\MassSchedule;
use App\Models\Media;
use App\Models\Post;
use App\Models\ScheduleNotice;
use App\Models\Service;
use App\Models\SpecialMass;
use App\Models\User;
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
