<?php

namespace App\Models;

use Database\Factories\ServiceRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One document or preparation a person must bring before registering.
 *
 * A structured list, not a paragraph (SVC-02): one record is one requirement, so
 * the public page can render a checklist and the print view stays readable.
 *
 * SVC-03 requires the service's "Terakhir diperbarui" to move when a child
 * changes. That touch is done by the controllers and the reorder action rather
 * than through Eloquent's `$touches`, because `$touches` reloads the parent
 * relation on every save and the application runs with lazy loading disabled
 * (ARCHITECTURE Part A section 6), which turns that reload into an exception.
 *
 * @property int $id
 * @property int $service_id
 * @property string $text
 * @property string|null $note
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Service $service
 */
#[Fillable([
    'service_id',
    'text',
    'note',
    'sort_order',
])]
class ServiceRequirement extends Model
{
    /** @use HasFactory<ServiceRequirementFactory> */
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
     * @param  Builder<ServiceRequirement>  $query
     * @return Builder<ServiceRequirement>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
