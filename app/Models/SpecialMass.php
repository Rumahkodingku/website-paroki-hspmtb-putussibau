<?php

namespace App\Models;

use Database\Factories\SpecialMassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A mass celebrated once, on a date: a holy day, a funeral, a Christmas.
 *
 * Unlike a recurring mass this has real dates, so start_at and end_at are UTC
 * (D-01) and rendered in app.display_timezone. A mass that has already happened
 * stays in the table — MASS-02 says so — it simply stops being selected.
 *
 * @property int $id
 * @property int $location_id
 * @property string $title
 * @property string|null $description
 * @property Carbon $start_at
 * @property Carbon|null $end_at
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Location $location
 * @property-read Collection<int, ScheduleNotice> $scheduleNotices
 */
#[Fillable([
    'location_id',
    'title',
    'description',
    'start_at',
    'end_at',
    'notes',
    'is_active',
])]
class SpecialMass extends Model
{
    /** @use HasFactory<SpecialMassFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<ScheduleNotice, $this>
     */
    public function scheduleNotices(): HasMany
    {
        return $this->hasMany(ScheduleNotice::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Special masses a visitor may still see.
     *
     * MASS-02: a mass whose start has passed is hidden from the public tab but
     * not removed, so the record stays for the parish's own history. The
     * location condition repeats MassSchedule::scopeActive for the same reason
     * MASS-03 gives.
     *
     * @param  Builder<SpecialMass>  $query
     * @return Builder<SpecialMass>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('start_at', '>=', now())
            ->whereHas('location', fn (Builder $locations) => $locations->where('is_active', true));
    }
}
