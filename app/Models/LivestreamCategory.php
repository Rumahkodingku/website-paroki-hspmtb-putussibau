<?php

namespace App\Models;

use Database\Factories\LivestreamCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A master category for livestreams.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Livestream> $livestreams
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'sort_order',
    'is_active',
])]
class LivestreamCategory extends Model
{
    /** @use HasFactory<LivestreamCategoryFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return HasMany<Livestream, $this>
     */
    public function livestreams(): HasMany
    {
        return $this->hasMany(Livestream::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<LivestreamCategory>  $query
     * @return Builder<LivestreamCategory>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<LivestreamCategory>  $query
     * @return Builder<LivestreamCategory>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
