<?php

namespace App\Models;

use Database\Factories\ServiceFaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One question and answer belonging to a single service.
 *
 * FAQs are per service rather than global, and ordered explicitly, so the public
 * accordion shows them in the order the parish decided.
 *
 * The service's updated_at is touched by the controller when a FAQ changes, not
 * through `$touches` — see ServiceRequirement's note (SVC-03, strict mode).
 *
 * @property int $id
 * @property int $service_id
 * @property string $question
 * @property string $answer
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Service $service
 */
#[Fillable([
    'service_id',
    'question',
    'answer',
    'sort_order',
])]
class ServiceFaq extends Model
{
    /** @use HasFactory<ServiceFaqFactory> */
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
     * @param  Builder<ServiceFaq>  $query
     * @return Builder<ServiceFaq>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
