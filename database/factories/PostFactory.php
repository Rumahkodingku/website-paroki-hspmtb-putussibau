<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 *
 * The default is a published article dated an hour ago, because that is what a
 * public page test wants most often and it keeps the common case one word long.
 * Every other state is named, so a test saying which case it means does not
 * have to know the default.
 *
 * Bodies are short sentences rather than lorem ipsum. A search test needs a
 * word to look for, and "contoh" in a paragraph of Latin would search fine
 * while proving nothing about the sanitizer.
 *
 * @see docs/DECISIONS.md D-43
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->sentence(4));

        return [
            'post_category_id' => PostCategory::factory(),
            'author_id' => User::factory(),
            // Null on purpose: the public byline falls back to the account's
            // name, and a test that asserts the fallback needs this to be null.
            'author_name' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(12),
            'content' => '<p>'.fake()->paragraph(3).'</p>',
            'featured_image' => null,
            'image_alt' => null,
            'scripture_reference' => null,
            'status' => PostStatus::Published,
            // An hour ago, so the article is inside the published scope by the
            // time the assertion runs even if the test is slow.
            'published_at' => Carbon::now()->subHour(),
            'is_pinned' => false,
            'view_count' => 0,
            'meta_title' => null,
            'meta_description' => null,
            'media_id' => null,
        ];
    }

    /**
     * Being written. POST-09: never public, whatever the URL says.
     */
    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * Waiting for a date in the future. PublishScheduledPosts moves it.
     */
    public function scheduled(\DateTimeInterface|bool $at = true): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Scheduled,
            'published_at' => $at instanceof \DateTimeInterface
                ? Carbon::instance($at)
                : Carbon::now()->addDay(),
        ]);
    }

    /**
     * Published with a date that has already passed.
     */
    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Published,
            'published_at' => Carbon::now()->subHour(),
        ]);
    }

    /**
     * Published but dated ahead of now.
     *
     * The case that catches a scope written as `where(status, published)` with
     * no date check: POST-02 says this must not appear until its time comes.
     */
    public function publishedInFuture(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Published,
            'published_at' => Carbon::now()->addWeek(),
        ]);
    }

    /**
     * No longer news, still kept. Never public.
     */
    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Archived,
            'published_at' => Carbon::now()->subMonth(),
        ]);
    }

    /**
     * In the featured strip.
     */
    public function pinned(): static
    {
        return $this->state(fn (): array => ['is_pinned' => true]);
    }

    /**
     * A reflection, which is the one category that shows a scripture quote.
     */
    public function renungan(): static
    {
        return $this->state(fn (): array => [
            'scripture_reference' => 'Yohanes 15.13',
        ]);
    }

    /**
     * Authored by somebody who wrote their name out, which is what
     * author_name is for.
     */
    public function by(string $name): static
    {
        return $this->state(fn (): array => ['author_name' => $name]);
    }
}
