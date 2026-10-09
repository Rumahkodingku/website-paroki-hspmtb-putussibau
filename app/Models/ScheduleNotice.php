<?php

namespace App\Models;

use App\Enums\MassKind;
use Database\Factories\ScheduleNoticeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A notice about a change to the schedule.
 *
 * Three optional targets, at most one of which may be filled: a location, a
 * recurring schedule, or a special mass. With none filled the notice applies to
 * everyone. Roadmap 4.4 requires that the references are consistent, and the
 * form request enforces exclusivity — see StoreScheduleNoticeRequest.
 *
 * starts_at and ends_at are UTC. ends_at is null means the notice has no end
 * date, which MASS-05 defines as still being shown.
 *
 * @property int $id
 * @property string $title
 * @property string $message
 * @property int|null $location_id
 * @property int|null $mass_schedule_id
 * @property int|null $special_mass_id
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Location|null $location
 * @property-read MassSchedule|null $massSchedule
 * @property-read SpecialMass|null $specialMass
 */
#[Fillable([
    'title',
    'message',
    'location_id',
    'mass_schedule_id',
    'special_mass_id',
    'starts_at',
    'ends_at',
    'is_active',
])]
class ScheduleNotice extends Model
{
    /** @use HasFactory<ScheduleNoticeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<MassSchedule, $this>
     */
    public function massSchedule(): BelongsTo
    {
        return $this->belongsTo(MassSchedule::class);
    }

    /**
     * @return BelongsTo<SpecialMass, $this>
     */
    public function specialMass(): BelongsTo
    {
        return $this->belongsTo(SpecialMass::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * What a notice points at, for the admin UI and for the public banner.
     *
     * Derived rather than stored: the three columns are constrained to at most
     * one non-null value, so there is exactly one answer and no way for a fourth
     * combination to appear.
     */
    public function targetKind(): ?MassKind
    {
        return match (true) {
            $this->mass_schedule_id !== null => MassKind::Rutin,
            $this->special_mass_id !== null => MassKind::Khusus,
            $this->location_id !== null => null,
            default => null,
        };
    }

    /**
     * True when the notice applies to everyone rather than to one target.
     */
    public function isGeneral(): bool
    {
        return $this->location_id === null
            && $this->mass_schedule_id === null
            && $this->special_mass_id === null;
    }

    /**
     * Notices a visitor may see right now.
     *
     * MASS-05: hidden when is_active is false, when starts_at is still ahead,
     * or when the current time is past ends_at. A null ends_at has no upper
     * bound, which is what a notice with no end date means.
     *
     * @param  Builder<ScheduleNotice>  $query
     * @return Builder<ScheduleNotice>
     */
    public function scopeCurrentlyVisible(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where(fn (Builder $window) => $window
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', $now));
    }
}
