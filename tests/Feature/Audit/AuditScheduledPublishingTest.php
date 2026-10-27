<?php

use App\Models\AuditEvent;
use App\Models\Post;
use Illuminate\Support\Carbon;

it('records one aggregate system event when scheduled articles are published', function () {
    $due = Post::factory()->scheduled(Carbon::now()->subMinute())->create();
    Post::factory()->scheduled(Carbon::now()->addDay())->create();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    $event = AuditEvent::query()->sole();

    expect($event->module)->toBe('news')
        ->and($event->action)->toBe('published')
        ->and($event->actor_type)->toBe('system')
        ->and($event->metadata['count'])->toBe(1)
        ->and($event->metadata['post_ids'])->toBe([$due->getKey()]);
});

it('writes nothing when no article is due', function () {
    Post::factory()->scheduled(Carbon::now()->addDay())->create();

    $this->artisan('posts:publish-scheduled')->assertSuccessful();

    expect(AuditEvent::query()->count())->toBe(0);
});
