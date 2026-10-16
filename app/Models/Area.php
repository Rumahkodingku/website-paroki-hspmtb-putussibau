<?php

namespace App\Models;

use App\Enums\AreaType;
use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A wilayah, lingkungan, or stasi: where a community serves (D-55 point 3).
 *
 * `areas` is deliberately not `locations` (a place a mass is celebrated) and not
 * `parish_service_areas` (the singleton narrative on the profile page). The three
 * have different purposes and are not merged.
 *
 * Two ways to take an area off the site, and they are not the same thing.
 * Deactivating is the everyday one: an inactive area is not offered for new
 * relationships, but relationships already made are not lost. Deleting is the
 * escape hatch reserved for super admins, and it is soft: the row survives and
 * the slug is not reused (D-55 point 7). AreaController::destroy refuses the
 * delete while the area still has children or is used by a community.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property AreaType $type
 * @property string|null $description
 * @property string|null $address
 * @property string|null $maps_url
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $leader_name
 * @property string|null $contact_phone
 * @property bool $show_contact
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Area|null $parent
 * @property-read Collection<int, Area> $children
 */
#[Fillable([
    'parent_id',
    'name',
    'slug',
    'type',
    'description',
    'address',
    'maps_url',
    'latitude',
    'longitude',
    'leader_name',
    'contact_phone',
    'show_contact',
    'sort_order',
    'is_active',
])]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return BelongsTo<Area, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    /**
     * @return HasMany<Area, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Area::class, 'parent_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AreaType::class,
            'show_contact' => 'boolean',
            // Decimal, not float: a coordinate is compared and stored as text,
            // and a float would turn -2.1234567 into a value that no longer
            // round-trips through the database unchanged.
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * A Google Maps URL for this area, or null when there is nothing to point at.
     *
     * The rule is Location::mapsUrl()'s, copied rather than shared: maps_url
     * wins, otherwise a coordinate pair, otherwise nothing. An address is never
     * used - a partial address produces a link to the wrong building, which is
     * worse than no button.
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
     * @param  Builder<Area>  $query
     * @return Builder<Area>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The stable ordering: sort_order, then name.
     *
     * @param  Builder<Area>  $query
     * @return Builder<Area>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isWilayah(): bool
    {
        return $this->type === AreaType::Wilayah;
    }

    public function isLingkungan(): bool
    {
        return $this->type === AreaType::Lingkungan;
    }

    public function isStasi(): bool
    {
        return $this->type === AreaType::Stasi;
    }

    /**
     * Bind `{area:slug}` on the public route to an active area only.
     *
     * The admin routes bind by id and must reach inactive areas, so only the
     * slug-bound public route gets this. An inactive or soft-deleted slug
     * therefore returns null and the route 404s (XC-E3).
     *
     * @param  string|int  $value
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        return $this->active()->where('slug', $value)->first();
    }
}
