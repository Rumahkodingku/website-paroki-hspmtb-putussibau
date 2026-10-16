<?php

namespace App\Models;

use Database\Factories\CommunityCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A master grouping for communities: Organisasi/Kategorial, Kelompok Doa,
 * Wilayah dan Lingkungan.
 *
 * Roadmap 5.1/1.1 moved categories out of the PRD's `community_types` enum into
 * Master Data, so an admin can rename, reorder and deactivate them without a
 * migration (D-55 point 1). The communities point here through a RESTRICT
 * foreign key: a category in use cannot be deleted, only deactivated.
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
 * @property-read Collection<int, Community> $communities
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'sort_order',
    'is_active',
])]
class CommunityCategory extends Model
{
    /** @use HasFactory<CommunityCategoryFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return HasMany<Community, $this>
     */
    public function communities(): HasMany
    {
        return $this->hasMany(Community::class);
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
     * Only categories a community may still be assigned to.
     *
     * @param  Builder<CommunityCategory>  $query
     * @return Builder<CommunityCategory>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The stable ordering: sort_order, then id.
     *
     * @param  Builder<CommunityCategory>  $query
     * @return Builder<CommunityCategory>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
