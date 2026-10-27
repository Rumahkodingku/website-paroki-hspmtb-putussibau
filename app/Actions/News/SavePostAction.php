<?php

namespace App\Actions\News;

use App\Enums\MediaStatus;
use App\Enums\PostStatus;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes an article, filling in the four things the form cannot be trusted with.
 *
 * It is an Action rather than two lines in the controller because it does four
 * things that have to happen together, and ARCHITECTURE.md Part A section 2
 * pulls a method out of the controller once it exceeds that:
 *
 *   1. stamps author_id from the session. Never from the request body — D-21's
 *      rule, and the reason author_id is not in the rules at all.
 *
 *   2. fills author_name from the account when the admin left it blank. The
 *      public byline should survive an admin leaving; see Post::displayAuthorName().
 *
 *   3. derives the slug when the field was left empty, and keeps the existing one
 *      when it was not (POST-01: an article that has been published does not
 *      change its slug because somebody edited the title).
 *
 *   4. stamps published_at = now() when an article is published without a date,
 *      and writes featured_image from the chosen media row.
 *
 * @see docs/DECISIONS.md D-43
 */
class SavePostAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes  the validated payload
     */
    public function handle(Post $post, array $attributes, User $author): Post
    {
        return DB::transaction(function () use ($post, $attributes, $author): Post {
            $isNew = ! $post->exists;
            $statusBefore = $isNew ? null : $post->status;
            $before = $isNew ? [] : $this->audit->snapshot($post, 'news');

            $post->fill([
                ...$attributes,
                'author_id' => $author->getKey(),
                'author_name' => $this->authorName($attributes['author_name'] ?? null, $author),
                'slug' => $this->resolveSlug($post, $attributes),
                'published_at' => $this->resolvePublishedAt($attributes),
                'featured_image' => $this->resolveFeaturedImage($post, $attributes),
            ]);

            $post->save();

            if ($isNew) {
                $this->audit->created('news', $post);
            } else {
                $this->audit->updated(
                    'news',
                    $post,
                    $before,
                    $this->statusAction($statusBefore, $post->status),
                );
            }

            return $post;
        });
    }

    private function statusAction(?PostStatus $before, PostStatus $after): string
    {
        if ($before === $after) {
            return 'updated';
        }

        return match ($after) {
            PostStatus::Published => 'published',
            PostStatus::Scheduled => 'scheduled',
            PostStatus::Archived => 'archived',
            PostStatus::Draft => 'unpublished',
        };
    }

    /**
     * The byline the public page will show.
     *
     * Taken once, at write time, rather than resolved on every read. That is the
     * point of author_name existing: an admin who leaves the parish should not
     * turn every article they wrote into a byline that reads "deleted user".
     */
    private function authorName(mixed $authorName, User $author): string
    {
        return is_string($authorName) && trim($authorName) !== ''
            ? trim($authorName)
            : $author->name;
    }

    /**
     * POST-01.
     *
     * A slug that was typed is kept as typed — including on an article that has
     * already been published, because a URL somebody has shared must not stop
     * working because somebody reworded a heading. A slug that was left empty is
     * derived from the title and made unique with a numeric suffix, because two
     * articles titled the same thing is ordinary and two identical URLs is not.
     */
    /** @param  array<string, mixed>  $attributes */
    private function resolveSlug(Post $post, array $attributes): string
    {
        $given = $attributes['slug'] ?? null;

        if (is_string($given) && trim($given) !== '') {
            return trim($given);
        }

        $base = Str::slug($attributes['title'] ?? '');
        $slug = $base;
        $suffix = 2;

        // Deterministic and bounded: slug, slug-2, slug-3. The count makes the
        // worst case a handful of queries on a collision, which is not a
        // concern for two admins writing articles.
        while (Post::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;

            if ($suffix > 100) {
                // A title that collides a hundred times is not a title, it is a
                // loop. Falling back to the key makes it unique without looping
                // forever, and the article is still saved rather than 500.
                $slug = $base.'-'.Str::random(6);

                break;
            }
        }

        return $slug === '' ? (string) Str::uuid() : $slug;
    }

    /**
     * PRD 9.6: status = published fills published_at = now() when it is empty.
     *
     * A scheduled article always has a date — the form request refuses one that
     * does not — so this only ever fires for `published`.
     */
    /** @param  array<string, mixed>  $attributes */
    private function resolvePublishedAt(array $attributes): mixed
    {
        $publishedAt = $attributes['published_at'] ?? null;

        if ($publishedAt !== null && $publishedAt !== '') {
            return $publishedAt;
        }

        return ($attributes['status'] ?? null) === PostStatus::Published->value
            ? Carbon::now()
            : null;
    }

    /**
     * The path the public page will load the featured image from.
     *
     * Written from the media row, never from the request: the admin cannot post
     * a path into a field and have the site render a file of their choosing. A
     * media row only becomes a reference once its variants exist, which is what
     * the rule on media_id checks — so the variant read here cannot 404.
     *
     * An article saved without choosing a new image keeps the one it has. That
     * is why featured_image is dropped from the payload in PostValidationRules:
     * a null in the form means "unchanged", not "remove my picture".
     */
    /** @param  array<string, mixed>  $attributes */
    private function resolveFeaturedImage(Post $post, array $attributes): ?string
    {
        $mediaId = $attributes['media_id'] ?? null;

        if ($mediaId === null) {
            return $post->exists ? $post->featured_image : null;
        }

        $media = Media::query()
            ->whereKey($mediaId)
            ->where('status', MediaStatus::Ready->value)
            ->first();

        if ($media === null) {
            // The rule should have caught this. Returning the existing image
            // rather than nulling it means a race with a failed upload costs
            // the new picture, not the old one.
            return $post->featured_image;
        }

        return $media->variantPath($this->largestVariantName());
    }

    /**
     * The name of the widest configured variant.
     *
     * Read from config('media.variants') rather than written as the literal
     * 'large', because the column stores a path and the path's directory carries
     * the variant name — hard-coding a name that the config can change would
     * leave every article pointing at a directory nothing writes to.
     */
    private function largestVariantName(): ?string
    {
        $variants = (array) config('media.variants');

        if ($variants === []) {
            return null;
        }

        $name = null;
        $widest = -1;

        foreach ($variants as $candidate => $width) {
            if ((int) $width > $widest) {
                $widest = (int) $width;
                $name = (string) $candidate;
            }
        }

        return $name;
    }
}
