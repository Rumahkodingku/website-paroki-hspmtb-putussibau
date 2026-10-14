<?php

namespace App\Models;

use Database\Factories\ServiceStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One step of a service's procedure.
 *
 * The number on the public page comes from this row's position (sort_order),
 * never from a number typed into the title, so reordering the list renumbers it.
 *
 * The service's updated_at is touched by the controller when a step changes,
 * not through `$touches` — see ServiceRequirement's note (SVC-03, strict mode).
 *
 * @property int $id
 * @property int $service_id
 * @property string $title
 * @property string|null $description
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Service $service
 */
#[Fillable([
    'service_id',
    'title',
    'description',
    'sort_order',
])]
class ServiceStep extends Model
{
    /** @use HasFactory<ServiceStepFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<ServiceStep>  $query
     * @return Builder<ServiceStep>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
