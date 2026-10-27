<?php

use App\Actions\News\SavePostAction;
use App\Enums\PostStatus;
use App\Models\AuditEvent;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function auditPostPayload(array $overrides = []): array
{
    return [
        'title' => 'Berita audit',
        'slug' => '',
        'post_category_id' => PostCategory::factory()->create()->getKey(),
        'author_name' => '',
        'excerpt' => 'Ringkasan.',
        'content' => '<p>Isi berita.</p>',
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

it('records one created event for a new article', function () {
    $user = superAdmin();

    $this->actingAs($user)
        ->post(route('posts.store'), auditPostPayload(['title' => 'Artikel Audit']))
        ->assertSessionHasNoErrors();

    $post = Post::query()->sole();
    $event = AuditEvent::query()->sole();

    expect($event->module)->toBe('news')
        ->and($event->action)->toBe('created')
        ->and($event->result)->toBe('success')
        ->and($event->actor_id)->toBe($user->getKey())
        ->and($event->subject_id)->toBe((string) $post->getKey())
        ->and($event->subject_label)->toBe('Artikel Audit')
        ->and($event->after)->toHaveKey('title', 'Artikel Audit')
        ->and($event->after)->not->toHaveKey('content');
});

it('records only changed fields and uses the specific status action', function () {
    $user = superAdmin();
    $post = Post::factory()->create([
        'author_id' => $user->getKey(),
        'status' => PostStatus::Draft,
        'published_at' => null,
    ]);

    $payload = auditPostPayload([
        'title' => 'Judul Baru',
        'post_category_id' => $post->post_category_id,
        'status' => PostStatus::Published->value,
        'slug' => $post->slug,
    ]);

    $this->actingAs($user)
        ->patch(route('posts.update', $post), $payload)
        ->assertSessionHasNoErrors();

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->action)->toBe('published')
        ->and($event->before)->toHaveKey('status', PostStatus::Draft->value)
        ->and($event->after)->toHaveKey('status', PostStatus::Published->value)
        ->and($event->before)->toHaveKey('title', $post->title)
        ->and($event->before)->not->toHaveKey('content');

    expect(AuditEvent::query()->where('action', 'updated')->count())->toBe(0);
});

it('records a deleted event with a snapshot of the removed row', function () {
    $user = superAdmin();
    $post = Post::factory()->create(['author_id' => $user->getKey()]);

    $this->actingAs($user)
        ->delete(route('posts.destroy', $post))
        ->assertRedirect();

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->action)->toBe('deleted')
        ->and($event->before)->toHaveKey('title', $post->title)
        ->and($event->after)->toBeNull();
});

it('does not write a success event when the business transaction rolls back', function () {
    $user = superAdmin();
    $category = PostCategory::factory()->create();
    $attributes = auditPostPayload([
        'post_category_id' => $category->getKey(),
        'title' => 'Akan dibatalkan',
    ]);

    try {
        DB::transaction(function () use ($attributes, $user): void {
            app(SavePostAction::class)->handle(new Post, $attributes, $user);

            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
        // The point of the test is what remains after the rollback.
    }

    expect(Post::query()->count())->toBe(0)
        ->and(AuditEvent::query()->count())->toBe(0);
});

it('keeps the actor snapshot after the account is removed', function () {
    $user = superAdmin();

    app(AuditLogger::class)->loginSucceeded($user);
    $user->delete();

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->actor_id)->toBeNull()
        ->and($event->actor_name)->toBe($user->name)
        ->and($event->actor_email)->toBe($user->email);
});

it('distinguishes system events from user events', function () {
    app(AuditLogger::class)->system(
        'media',
        'media_processing_completed',
        'success',
        'Pemrosesan gambar selesai.',
    );

    $event = AuditEvent::query()->sole();

    expect($event->actor_type)->toBe('system')
        ->and($event->actor_id)->toBeNull()
        ->and($event->result)->toBe('success');
});

it('stores occurred_at in UTC', function () {
    $user = superAdmin();

    app(AuditLogger::class)->loginSucceeded($user);

    expect(AuditEvent::query()->sole()->occurred_at->getTimezone()->getName())
        ->toBe('UTC');
});
