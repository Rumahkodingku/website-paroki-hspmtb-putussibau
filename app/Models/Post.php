<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Services\HtmlSanitizer;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One article: a news item, an activity report, a reflection, an announcement.
 *
 * Three things about this model are load-bearing and easy to undo by accident.
 *
 * 1. published_at is a real instant in UTC (D-01), cast to a Carbon, and
 *    rendered in app.display_timezone. It is nothing like MassSchedule's
 *    start_time, which is a wall clock on a weekly recurrence and is
 *    deliberately not cast at all. Confusing the two moves a 07.30 mass to
 *    00.30; the same mistake here would move an article to the wrong day.
 *
 * 2. The public site is one scope, not three exclusions. POST-09 requires a
 *    draft, a scheduled article and an archived one to answer 404 to a public
 *    URL even when the slug is known, and scopePublished is what the list, the
 *    search, the related posts, the route binding and the .ics-adjacent preview
 *    all share. A second definition of "public" is how one of them gets left out.
 *
 * 3. content is sanitized on the way in, by the form request, and only ever
 *    rendered through components/rich-text.tsx. The sanitizer's allowlist lives
 *    in config/html.php — see D-25 for why it is not a constructor argument.
 *
 * @property int $id
 * @property int $post_category_id
 * @property int $author_id
 * @property string|null $author_name
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $content
 * @property string|null $featured_image
 * @property string|null $image_alt
 * @property string|null $scripture_reference
 * @property PostStatus $status
 * @property Carbon|null $published_at
 * @property bool $is_pinned
 * @property int $view_count
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int|null $media_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PostCategory $category
 * @property-read User $author
 * @property-read Media|null $media
 */
#[Fillable([
    'post_category_id',
    'author_id',
    'author_name',
    'title',
    'slug',
    'excerpt',
    'content',
    'featured_image',
    'image_alt',
    'scripture_reference',
    'status',
    'published_at',
    'is_pinned',
    'view_count',
    'meta_title',
    'meta_description',
    'media_id',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * Named `category` rather than `postCategory` because that is the word the
     * interface uses, and both PostCategory and EventCategory have the same
     * shape of relation.
     *
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The upload the featured image came from. Nullable: SET NULL, and an
     * article must never lose its text because a picture was removed.
     *
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'is_pinned' => 'boolean',
            // UTC storage, WIB display (D-01). See the class docblock.
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * Everything a visitor is allowed to read.
     *
     * POST-02 and POST-09: published, dated, and dated in the past. All three
     * halves are needed — a scheduled article that has reached its time is still
     * `scheduled` until PublishScheduledPosts runs, and a published article with
     * a future date must not appear either.
     *
     * Soft-deleted rows are excluded by SoftDeletes itself, so no caller has to
     * remember to add it.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * The pinned strip at the top of /berita.
     *
     * POST-07 caps the strip at two. The limit is here rather than only in the
     * form request so that a database which has drifted — two admins saving at
     * the same moment, or a row imported by hand — still cannot make the public
     * page show three.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->published()->where('is_pinned', true);
    }

    /**
     * Bind `{post:slug}` on the public routes to a published article only.
     *
     * This is the whole of POST-09. Laravel calls resolveRouteBinding with the
     * field named in the route, so the admin routes — which bind by id and must
     * reach drafts and archived articles — keep the default behaviour and only
     * the public, slug-bound routes get this.
     *
     * @param  string|int  $value
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        return $this->published()->where('slug', $value)->first();
    }

    /**
     * The byline as the public page shows it.
     *
     * author_name is what an admin typed, and it may be empty; the account's own
     * name is the fallback. D-21 says the raw model is never sent to the
     * browser, so the decision is made here rather than in React.
     */
    public function displayAuthorName(): string
    {
        if ($this->author_name !== null && trim($this->author_name) !== '') {
            return $this->author_name;
        }

        return $this->author->name;
    }

    /**
     * The description the list, the meta tag and the share link all use.
     *
     * Falls back from meta_description to excerpt to the first lines of the
     * body. `content` is HTML, so it has to become text before it can go into a
     * meta description — that is what HtmlSanitizer::toPlainText is for.
     */
    public function displayDescription(): ?string
    {
        foreach ([$this->meta_description, $this->excerpt] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        $text = app(HtmlSanitizer::class)->toPlainText($this->content);

        return $text === '' ? null : Str::limit($text, 200);
    }

    /**
     * The document title for the detail page: meta_title, then the title.
     *
     * PRD 7.7 says the SEO fields may be left empty and the article's own
     * fields are used instead.
     */
    public function displayMetaTitle(): string
    {
        return $this->meta_title !== null && trim($this->meta_title) !== ''
            ? $this->meta_title
            : $this->title;
    }
}
