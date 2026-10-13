<?php

namespace App\Models;

use Database\Factories\ParishProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The parish profile — a singleton row.
 *
 * There is exactly one active profile. The singleton is enforced in the
 * application rather than by a database constraint: `current()` returns the
 * first row or an unsaved instance, and the update controller uses
 * `updateOrCreate` so a second row can never be produced. A generated unique
 * column would only make the migration harder to read.
 *
 * `description` and `history_description` are sanitized HTML on the way in (in
 * the form request) and only ever rendered through components/rich-text.tsx.
 *
 * @property int $id
 * @property string $name
 * @property string|null $short_description
 * @property string|null $description
 * @property string|null $history_description
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property int|null $established_year
 * @property int|null $logo_media_id
 * @property string|null $logo_alt
 * @property int|null $cover_media_id
 * @property string|null $cover_alt
 * @property string|null $map_url
 * @property string|null $video_url
 * @property bool $is_published
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Media|null $logoMedia
 * @property-read Media|null $coverMedia
 */
#[Fillable([
    'name',
    'short_description',
    'description',
    'history_description',
    'address',
    'phone',
    'email',
    'website',
    'established_year',
    'logo_media_id',
    'logo_alt',
    'cover_media_id',
    'cover_alt',
    'map_url',
    'video_url',
    'is_published',
])]
class ParishProfile extends Model
{
    /** @use HasFactory<ParishProfileFactory> */
    use HasFactory;

    /**
     * The upload the logo came from. Nullable: SET NULL, never CASCADE.
     *
     * @return BelongsTo<Media, $this>
     */
    public function logoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'established_year' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * The one profile, or an empty instance when none exists yet.
     *
     * No write happens here: a GET that renders the edit form must not create
     * a row as a side effect. The controller saves with updateOrCreate.
     */
    public static function current(): self
    {
        return static::query()->firstOrNew([]);
    }

    /**
     * Everything a visitor is allowed to read.
     *
     * @param  Builder<ParishProfile>  $query
     * @return Builder<ParishProfile>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
