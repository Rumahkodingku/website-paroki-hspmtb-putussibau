<?php

namespace App\Models;

use App\Enums\CommunityStatus;
use Database\Factories\CommunityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One community: a group, organisation, or neighbourhood within the parish.
 *
 * Three things about this model are load-bearing and easy to undo by accident.
 *
 * 1. Two independent flags. `status` is what the community *is*
 *    (forming/active/inactive) and `is_published` is whether it is shown. Only
 *    `active` **and** `published` reaches the public site (roadmap 5.2.2), and
 *    that is one scope rather than a condition repeated per query.
 *
 * 2. The public site is one scope, not an exclusion. scopePublished is what the
 *    list, the detail binding and any future surface share, so a draft, forming,
 *    or inactive community answers 404 (XC-E3) everywhere instead of in
 *    whichever query remembered to check.
 *
 * 3. vision and mission are sanitized HTML (D-60), cleaned by
 *    CommunityValidationRules on the way in and rendered through
 *    components/rich-text.tsx. `description` and the rest of the long fields stay
 *    plain text, so "a string in this table" is not the same thing on every row.
 *
 * 4. Contact is published only with consent. XC-P1 is enforced in the resource
 *    on the read path, not only in the UI: when `show_contact` is false the name
 *    and phone never reach the browser.
 *
 * @property int $id
 * @property int $community_category_id
 * @property string $name
 * @property string $slug
 * @property int|null $logo_media_id
 * @property string|null $logo_alt
 * @property int|null $thumbnail_media_id
 * @property string|null $thumbnail_alt
 * @property string|null $description
 * @property string|null $vision
 * @property string|null $mission
 * @property string|null $meeting_schedule
 * @property string|null $meeting_location
 * @property string|null $eligibility
 * @property string|null $joining_info
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property bool $show_contact
 * @property string|null $facebook
 * @property string|null $instagram
 * @property string|null $tiktok
 * @property string|null $youtube
 * @property string|null $website
 * @property CommunityStatus $status
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read CommunityCategory $communityCategory
 * @property-read Media|null $logoMedia
 * @property-read Media|null $thumbnailMedia
 */
#[Fillable([
    'community_category_id',
    'name',
    'slug',
    'logo_media_id',
    'logo_alt',
    'thumbnail_media_id',
    'thumbnail_alt',
    'description',
    'vision',
    'mission',
    'meeting_schedule',
    'meeting_location',
    'eligibility',
    'joining_info',
    'contact_name',
    'contact_phone',
    'show_contact',
    'facebook',
    'instagram',
    'tiktok',
    'youtube',
    'website',
    'status',
    'is_published',
    'published_at',
])]
class Community extends Model
{
    /** @use HasFactory<CommunityFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return BelongsTo<CommunityCategory, $this>
     */
    public function communityCategory(): BelongsTo
    {
        return $this->belongsTo(CommunityCategory::class);
    }

    /**
     * The uploaded logo shown on the detail page.
     *
     * Nullable, SET NULL: a community without a logo is valid, and removing the
     * media row must not remove the community.
     *
     * @return BelongsTo<Media, $this>
     */
    public function logoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    /**
     * The single representative image on the card and detail header.
     *
     * Not a gallery: the multi-photo module is Phase 8 (roadmap 3.3).
     *
     * @return BelongsTo<Media, $this>
     */
    public function thumbnailMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'thumbnail_media_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CommunityStatus::class,
            'is_published' => 'boolean',
            'show_contact' => 'boolean',
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * Only what a visitor may see (roadmap 5.2.2, XC-E3).
     *
     * Soft-deleted rows are already excluded by SoftDeletes, so this is the two
     * flags: active status and published.
     *
     * @param  Builder<Community>  $query
     * @return Builder<Community>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('status', CommunityStatus::Active);
    }

    /**
     * The stable public/admin ordering: name, then id.
     *
     * @param  Builder<Community>  $query
     * @return Builder<Community>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name')->orderBy('id');
    }

    /**
     * Bind `{community:slug}` on the public route to a published community only.
     *
     * The admin routes bind by id and must reach drafts, so only the slug-bound
     * public route gets this. A draft, forming, inactive, or soft-deleted slug
     * therefore returns null and the route 404s (XC-E3).
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
