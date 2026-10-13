<?php

namespace App\Models;

use Database\Factories\PastorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A pastor's identity: name, optional title, photo and biography.
 *
 * Assignment periods live on PastorAssignment, not here, so the same person can
 * hold several periods and history survives a change of office (D-52).
 *
 * @property int $id
 * @property string $name
 * @property string|null $title
 * @property int|null $photo_media_id
 * @property string|null $photo_alt
 * @property string|null $bio
 * @property int $sort_order
 * @property bool $is_published
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, PastorAssignment> $assignments
 * @property-read PastorAssignment|null $currentAssignment
 * @property-read Media|null $photoMedia
 */
#[Fillable([
    'name',
    'title',
    'photo_media_id',
    'photo_alt',
    'bio',
    'sort_order',
    'is_published',
])]
class Pastor extends Model
{
    /** @use HasFactory<PastorFactory> */
    use HasFactory;

    /**
     * @return HasMany<PastorAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PastorAssignment::class);
    }

    /**
     * The assignment currently in office, if any.
     *
     * @return HasOne<PastorAssignment, $this>
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(PastorAssignment::class)->where('is_current', true);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function photoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Pastor>  $query
     * @return Builder<Pastor>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Pastor>  $query
     * @return Builder<Pastor>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
