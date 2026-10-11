<?php

namespace App\Models;

use App\Enums\LocationType;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A place where masses are celebrated: the parish church, a stasi, or a chapel.
 *
 * Two ways to take a location off the site, and they are not the same thing.
 * Deactivating is the everyday one and the only one a secretary needs: PRD 7.3
 * admin says "nonaktifkan, tidak hapus", MASS-03 requires new schedules to
 * point at an active location, and MASS-07 requires the schedules behind a
 * deactivated one to disappear from the public site.
 *
 * Deleting is the other, added later and reserved for super admins — see D-40,
 * which superseded D-34 section 1 and closed the ADM-05 divergence. It is a
 * soft delete, so the row and its slug survive in the table; the deletion is
 * simply hidden from every query. Roadmap 4.5 still holds and is enforced in
 * LocationController::destroy(): a location with schedules or special masses
 * cannot be deleted, because both of those tables point here ON DELETE
 * RESTRICT.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property LocationType $type
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $maps_url
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, MassSchedule> $massSchedules
 * @property-read Collection<int, SpecialMass> $specialMasses
 * @property-read Collection<int, ScheduleNotice> $scheduleNotices
 */
#[Fillable([
    'name',
    'slug',
    'type',
    'address',
    'latitude',
    'longitude',
    'maps_url',
    'notes',
    'is_active',
])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return HasMany<MassSchedule, $this>
     */
    public function massSchedules(): HasMany
    {
        return $this->hasMany(MassSchedule::class);
    }

    /**
     * @return HasMany<SpecialMass, $this>
     */
    public function specialMasses(): HasMany
    {
        return $this->hasMany(SpecialMass::class);
    }

    /**
     * @return HasMany<ScheduleNotice, $this>
     */
    public function scheduleNotices(): HasMany
    {
        return $this->hasMany(ScheduleNotice::class);
    }

    /**
     * The schedules a visitor can still see, counting only the ones whose own
     * is_active is true.
     *
     * MASS-07 asks the admin UI to warn before a location is deactivated,
     * because the schedules behind it stop being published. Counting them on
     * the model rather than in the controller keeps that number next to the
     * relation it describes.
     */
    public function activeMassScheduleCount(): int
    {
        return $this->massSchedules()
            ->where('is_active', true)
            ->count();
    }

    /**
     * A Google Maps URL for this location, or null when there is nothing to
     * point at.
     *
     * Built here rather than in React so the rule lives in one place (roadmap
     * 7.2, 8): maps_url wins, otherwise a coordinate pair, otherwise nothing.
     * An address is never used — a partial address produces a link to the wrong
     * building, which is worse than no button.
     */
    public function mapsUrl(): ?string
    {
        if ($this->maps_url !== null && trim($this->maps_url) !== '') {
            return $this->maps_url;
        }

        if ($this->latitude !== null && $this->longitude !== null) {
            return sprintf(
                'https://www.google.com/maps/search/?api=1&query=%s,%s',
                $this->latitude,
                $this->longitude
            );
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'is_active' => 'boolean',
            // Decimal, not float: a coordinate is compared and stored as text,
            // and a float would turn -2.1234567 into a value that no longer
            // round-trips through the database unchanged.
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * Only locations a visitor may see.
     *
     * The single definition of "published" for a location, used by the public
     * queries, by MassSchedule::scopeActive and by the resolver — so MASS-03 and
     * MASS-07 cannot be satisfied in one place and forgotten in another.
     *
     * @param  Builder<Location>  $query
     * @return Builder<Location>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
