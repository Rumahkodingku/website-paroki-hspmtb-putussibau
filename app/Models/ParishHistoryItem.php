<?php

namespace App\Models;

use Database\Factories\ParishHistoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One event on the parish history timeline.
 *
 * `year` and `date` are both nullable and independent: a test must not be
 * forced to invent a date, and plenty of parish history is known by year only
 * (roadmap 6.2). When both are set, `date` is the precise one and orders first.
 *
 * `description` is sanitized HTML, rendered through components/rich-text.tsx.
 *
 * @property int $id
 * @property string $title
 * @property int|null $year
 * @property Carbon|null $date
 * @property string|null $description
 * @property int|null $media_id
 * @property string|null $image_alt
 * @property int $sort_order
 * @property bool $is_published
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Media|null $media
 */
#[Fillable([
    'title',
    'year',
    'date',
    'description',
    'media_id',
    'image_alt',
    'sort_order',
    'is_published',
])]
class ParishHistoryItem extends Model
{
    /** @use HasFactory<ParishHistoryItemFactory> */
    use HasFactory;

    /**
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
            'year' => 'integer',
            'date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<ParishHistoryItem>  $query
     * @return Builder<ParishHistoryItem>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * The single public ordering: sort_order, then year, then id.
     *
     * Deterministic on purpose — without the id tie-breaker two items with the
     * same sort_order and year can swap between loads.
     *
     * @param  Builder<ParishHistoryItem>  $query
     * @return Builder<ParishHistoryItem>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('year')->orderBy('id');
    }
}
