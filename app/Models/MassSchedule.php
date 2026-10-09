<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Database\Factories\MassScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A mass that recurs every week: which day, what time, and where.
 *
 * start_time is deliberately NOT cast. A TIME column holding "07:30" is a wall
 * clock reading — PRD D-02 says so, because a weekly recurrence is not bound to
 * a date and converting it would shift it by the UTC offset. Casting it to
 * datetime would turn a 07.30 mass into 00.30 and move "the next mass" to the
 * wrong day entirely. The value stays the string MySQL gives back, 'H:i:s'.
 *
 * @property int $id
 * @property int $location_id
 * @property DayOfWeek $day_of_week
 * @property string $start_time
 * @property string $name
 * @property string|null $language
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Location $location
 * @property-read Collection<int, ScheduleNotice> $scheduleNotices
 */
#[Fillable([
    'location_id',
    'day_of_week',
    'start_time',
    'name',
    'language',
    'notes',
    'is_active',
])]
class MassSchedule extends Model
{
    /** @use HasFactory<MassScheduleFactory> */
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
            'day_of_week' => DayOfWeek::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * The schedules a visitor may see: active, and at an active location.
     *
     * Both halves belong to this scope rather than to the query that uses it.
     * MASS-03 makes the active location a requirement of the schedule, and
     * MASS-07 says a schedule on a deactivated location must not be published —
     * so a query that filtered only is_active would quietly publish masses at a
     * location the parish has switched off.
     *
     * @param  Builder<MassSchedule>  $query
     * @return Builder<MassSchedule>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('location', fn (Builder $locations) => $locations->where('is_active', true));
    }

    /**
     * "07.30 WIB" — the public display format from PRD XC-T2.
     *
     * Formatted from the string rather than from a Carbon instance precisely
     * because there is no timezone involved. A time format would be the one
     * place in this model where the UTC offset could be applied by accident.
     */
    public function displayTime(): string
    {
        return str_replace(':', '.', substr($this->start_time, 0, 5)).' WIB';
    }
}
