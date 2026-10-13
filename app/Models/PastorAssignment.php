<?php

namespace App\Models;

use App\Enums\PastorPosition;
use Database\Factories\PastorAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One assignment period of one pastor.
 *
 * `is_current` marks the pastor in office. At most one row in the whole table
 * may be current; that is enforced by SetCurrentPastorAction inside a
 * transaction, not by the database.
 *
 * Years are nullable because the parish may not know them (D-52).
 *
 * @property int $id
 * @property int $pastor_id
 * @property PastorPosition $position
 * @property int|null $start_year
 * @property int|null $end_year
 * @property bool $is_current
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Pastor $pastor
 */
#[Fillable([
    'pastor_id',
    'position',
    'start_year',
    'end_year',
    'is_current',
    'description',
])]
class PastorAssignment extends Model
{
    /** @use HasFactory<PastorAssignmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Pastor, $this>
     */
    public function pastor(): BelongsTo
    {
        return $this->belongsTo(Pastor::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => PastorPosition::class,
            'start_year' => 'integer',
            'end_year' => 'integer',
            'is_current' => 'boolean',
        ];
    }

    /**
     * @param  Builder<PastorAssignment>  $query
     * @return Builder<PastorAssignment>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
