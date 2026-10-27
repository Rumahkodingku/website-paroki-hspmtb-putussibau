<?php

use App\Models\AuditEvent;
use App\Models\CommunityCategory;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\PostCategory;
use App\Models\ServiceCategory;

it('audits a mass schedule create and duplicate', function () {
    $user = superAdmin();
    $sourceLocation = Location::factory()->create(['is_active' => true]);
    $targetLocation = Location::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->post(route('mass-schedules.store'), [
            'location_id' => $sourceLocation->getKey(),
            'day_of_week' => 0,
            'start_time' => '07:30',
            'name' => 'Misa Audit',
            'notes' => '',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    $source = MassSchedule::query()->sole();
    $created = AuditEvent::query()->latest('id')->firstOrFail();

    expect($created->module)->toBe('mass_schedules')
        ->and($created->action)->toBe('created')
        ->and($created->subject_id)->toBe((string) $source->getKey());

    $this->actingAs($user)
        ->post(route('mass-schedules.duplicate', $source), [
            'location_id' => $targetLocation->getKey(),
        ])
        ->assertSessionHasNoErrors();

    $duplicated = AuditEvent::query()->latest('id')->firstOrFail();

    expect($duplicated->action)->toBe('duplicated')
        ->and($duplicated->metadata['source_id'])->toBe($source->getKey())
        ->and($duplicated->metadata['destination_location_id'])->toBe($targetLocation->getKey());
});

it('audits a parish profile update', function () {
    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), [
            'description' => '<p>Profil paroki.</p>',
            'established_year' => 2000,
            'cover_media_id' => null,
            'cover_alt' => null,
            'video_url' => null,
        ])
        ->assertSessionHasNoErrors();

    expect(AuditEvent::query()->latest('id')->firstOrFail()->module)
        ->toBe('parish_profile');
});

it('audits a service create', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), [
            'title' => 'Layanan Audit',
            'slug' => 'layanan-audit',
            'service_category_id' => $category->getKey(),
            'thumbnail_media_id' => null,
            'thumbnail_alt' => null,
            'summary' => 'Ringkasan.',
            'introduction' => '<p>Pengantar.</p>',
            'schedule_info' => null,
            'fee_info' => null,
            'contact_name' => null,
            'contact_phone' => null,
            'form_url' => null,
            'form_button_label' => 'Daftar',
            'is_form_open' => false,
            'form_closed_message' => null,
            'is_emergency' => false,
            'is_active' => false,
            'sort_order' => 0,
        ])
        ->assertSessionHasNoErrors();

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->module)->toBe('services')
        ->and($event->action)->toBe('created');
});

it('audits a community create', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), [
            'name' => 'Komunitas Audit',
            'slug' => 'komunitas-audit',
            'community_category_id' => $category->getKey(),
            'logo_media_id' => null,
            'logo_alt' => null,
            'thumbnail_media_id' => null,
            'thumbnail_alt' => null,
            'description' => 'Deskripsi.',
            'vision' => null,
            'mission' => null,
            'meeting_schedule' => null,
            'meeting_location' => null,
            'eligibility' => null,
            'joining_info' => null,
            'contact_name' => null,
            'contact_phone' => null,
            'show_contact' => false,
            'facebook' => null,
            'instagram' => null,
            'tiktok' => null,
            'youtube' => null,
            'website' => null,
            'status' => 'forming',
            'is_published' => false,
        ])
        ->assertSessionHasNoErrors();

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->module)->toBe('communities')
        ->and($event->action)->toBe('created');
});

it('audits an area create', function () {
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), [
            'name' => 'Area Audit',
            'slug' => 'area-audit',
            'type' => 'wilayah',
            'parent_id' => null,
            'description' => null,
            'leader_name' => null,
            'contact_phone' => null,
            'show_contact' => false,
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->module)->toBe('areas')
        ->and($event->action)->toBe('created');
});

it('audits a gallery album create', function () {
    $this->actingAs(superAdmin())
        ->post(route('galleries.store'), [
            'title' => 'Galeri Audit',
            'slug' => 'galeri-audit',
            'description' => 'Deskripsi.',
            'event_date' => '2026-10-01',
            'location' => 'Gereja',
            'is_published' => false,
        ])
        ->assertSessionHasNoErrors();

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->module)->toBe('galleries')
        ->and($event->action)->toBe('created');
});

it('audits a post category create and delete', function () {
    $this->actingAs(superAdmin())
        ->post(route('post-categories.store'), [
            'name' => 'Kategori Audit',
            'slug' => 'kategori-audit',
            'sort_order' => 0,
        ])
        ->assertSessionHasNoErrors();

    $category = PostCategory::query()->sole();
    $created = AuditEvent::query()->latest('id')->firstOrFail();

    expect($created->module)->toBe('post_categories')
        ->and($created->action)->toBe('created');

    $this->actingAs(superAdmin())
        ->delete(route('post-categories.destroy', $category))
        ->assertSessionHasNoErrors();

    $deleted = AuditEvent::query()->latest('id')->firstOrFail();

    expect($deleted->action)->toBe('deleted')
        ->and($deleted->subject_label)->toBe('Kategori Audit');
});
