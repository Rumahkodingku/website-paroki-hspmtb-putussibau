<?php

namespace App\Models;

use Database\Factories\GalleryAlbumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One gallery album: a set of photos documenting one event.
 *
 * Four things about this model are load-bearing and easy to undo by accident.
 *
 * 1. `is_published` is the whole publication state (D-65). There is no status
 *    enum and no archived status: PRD 9.10 describes a boolean, only two states
 *    exist, and an enum with two cases would be a layer around the same flag.
 *
 * 2. The public site is one scope, not an exclusion. scopePublished is what the
 *    list, the detail binding and any future surface share, so a draft or a
 *    soft-deleted album answers 404 (XC-E3) everywhere instead of in whichever
 *    query remembered to check.
 *
 * 3. `cover_photo_id` points at a photo in this album, not at a Media row. A
 *    null cover falls back to the first photo by sort_order (GAL-07), and a
 *    photo that leaves the album cannot leave a dangling cover because the
 *    foreign key is SET NULL.
 *
 * 4. `event_date` is a calendar date, not an instant. It is a wall clock and
 *    carries no timezone, so it is formatted with toDateString() on the way out
 *    rather than through a datetime conversion.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property Carbon|null $event_date
 * @property string|null $location
 * @property int|null $cover_photo_id
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read GalleryPhoto|null $coverPhoto
 * @property-read Collection<int, GalleryPhoto> $photos
 */
#[Fillable([
    'title',
    'slug',
    'description',
    'event_date',
    'location',
    'cover_photo_id',
    'is_published',
    'published_at',
    'created_by',
    'updated_by',
])]
class GalleryAlbum extends Model
{
    /** @use HasFactory<GalleryAlbumFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The photos in this album, in the order the admin saved.
     *
     * Ordered by sort_order then id so the order is total: two photos at the
     * same position cannot swap between requests.
     *
     * @return HasMany<GalleryPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The chosen cover, or null to fall back to the first photo (GAL-07).
     *
     * @return BelongsTo<GalleryPhoto, $this>
     */
    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(GalleryPhoto::class, 'cover_photo_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_published' => 'boolean',
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * Only what a visitor may see (XC-E3).
     *
     * Soft-deleted rows are already excluded by SoftDeletes, so this is the
     * single flag.
     *
     * @param  Builder<GalleryAlbum>  $query
     * @return Builder<GalleryAlbum>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * The stable public ordering: newest event first.
     *
     * event_date is nullable, and MySQL sorts nulls first ascending, so the raw
     * first clause keeps albums without a date at the end. published_at and id
     * make the order total, which is the only way paginating over it means
     * anything.
     *
     * @param  Builder<GalleryAlbum>  $query
     * @return Builder<GalleryAlbum>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw('event_date is null')
            ->orderByDesc('event_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * Bind `{gallery:slug}` on the public route to a published album only.
     *
     * The admin routes bind by id and must reach drafts, so only the slug-bound
     * public route gets this. A draft or soft-deleted slug therefore returns
     * null and the route 404s (XC-E3).
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
}
