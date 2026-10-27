<?php

namespace App\Models;

use Database\Factories\AuditEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One recorded administrative event.
 *
 * The table is append-only through the application: there is no update or
 * delete endpoint, and the model refuses instance updates and deletions. Mass
 * updates bypass model events, so the guarantee is an application one, not a
 * database one; docs/audit-log.md records that limit.
 *
 * @property int $id
 * @property int|null $actor_id
 * @property string $actor_type
 * @property string|null $actor_name
 * @property string|null $actor_email
 * @property string $action
 * @property string $module
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $subject_label
 * @property string $description
 * @property string $result
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property array<string, mixed>|null $metadata
 * @property string|null $request_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $occurred_at
 * @property Carbon $created_at
 */
#[Fillable([
    'actor_id',
    'actor_type',
    'actor_name',
    'actor_email',
    'action',
    'module',
    'subject_type',
    'subject_id',
    'subject_label',
    'description',
    'result',
    'before',
    'after',
    'metadata',
    'request_id',
    'ip_address',
    'user_agent',
    'occurred_at',
    'idempotency_key',
])]
class AuditEvent extends Model
{
    /** @use HasFactory<AuditEventFactory> */
    use HasFactory;

    /**
     * There is no updated_at column: an event is written once.
     */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('occurred_at')->orderByDesc('id');
    }

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    public function scopeOccurredBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    public function scopeForActor(Builder $query, int|string $actor): Builder
    {
        if (is_int($actor)) {
            return $query->where('actor_id', $actor);
        }

        return $query->where('actor_type', $actor);
    }

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    public function scopeForResult(Builder $query, string $result): Builder
    {
        return $query->where('result', $result);
    }

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $inner) use ($search): void {
            $inner->where('description', 'like', "%{$search}%")
                ->orWhere('subject_label', 'like', "%{$search}%")
                ->orWhere('actor_name', 'like', "%{$search}%");
        });
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Audit events are append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('Audit events are append-only.');
        });
    }
}
