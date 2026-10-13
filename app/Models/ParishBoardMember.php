<?php

namespace App\Models;

use Database\Factories\ParishBoardMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One member of one leadership period.
 *
 * Every member belongs to exactly one period; deleting a period cascades to its
 * members (the admin confirm dialog names the count first).
 *
 * @property int $id
 * @property int $parish_board_period_id
 * @property string $name
 * @property string $position
 * @property int|null $photo_media_id
 * @property string|null $photo_alt
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_published
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ParishBoardPeriod $period
 * @property-read Media|null $photoMedia
 */
#[Fillable([
    'parish_board_period_id',
    'name',
    'position',
    'photo_media_id',
    'photo_alt',
    'description',
    'sort_order',
    'is_published',
])]
class ParishBoardMember extends Model
{
    /** @use HasFactory<ParishBoardMemberFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ParishBoardPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(ParishBoardPeriod::class, 'parish_board_period_id');
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
     * @param  Builder<ParishBoardMember>  $query
     * @return Builder<ParishBoardMember>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<ParishBoardMember>  $query
     * @return Builder<ParishBoardMember>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
