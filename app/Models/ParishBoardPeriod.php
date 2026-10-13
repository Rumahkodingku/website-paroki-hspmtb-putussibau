<?php

namespace App\Models;

use Database\Factories\ParishBoardPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One leadership term of the Parish Pastoral Council.
 *
 * `is_active` is operational (the term currently in office); `is_published` is
 * public visibility. At most one period may be active, enforced by
 * ActivateBoardPeriodAction inside a transaction (PRD PROF-01, AC-PROF-5).
 *
 * Years are nullable and stored as `unsignedSmallInteger` (D-52).
 *
 * @property int $id
 * @property string $name
 * @property int|null $start_year
 * @property int|null $end_year
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_published
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ParishBoardMember> $members
 */
#[Fillable([
    'name',
    'start_year',
    'end_year',
    'description',
    'is_active',
    'is_published',
    'sort_order',
])]
class ParishBoardPeriod extends Model
{
    /** @use HasFactory<ParishBoardPeriodFactory> */
    use HasFactory;

    /**
     * @return HasMany<ParishBoardMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(ParishBoardMember::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_year' => 'integer',
            'end_year' => 'integer',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<ParishBoardPeriod>  $query
     * @return Builder<ParishBoardPeriod>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<ParishBoardPeriod>  $query
     * @return Builder<ParishBoardPeriod>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<ParishBoardPeriod>  $query
     * @return Builder<ParishBoardPeriod>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('start_year')->orderByDesc('id');
    }
}
