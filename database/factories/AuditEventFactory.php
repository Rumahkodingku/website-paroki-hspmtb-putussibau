<?php

namespace Database\Factories;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => null,
            'actor_type' => 'user',
            'actor_name' => fake()->name(),
            'actor_email' => fake()->safeEmail(),
            'action' => 'updated',
            'module' => 'news',
            'subject_type' => null,
            'subject_id' => null,
            'subject_label' => null,
            'description' => fake()->sentence(),
            'result' => 'success',
            'before' => null,
            'after' => null,
            'metadata' => null,
            'request_id' => null,
            'ip_address' => null,
            'user_agent' => null,
            'occurred_at' => Carbon::now(),
            'idempotency_key' => null,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (): array => [
            'actor_id' => null,
            'actor_type' => 'system',
            'actor_name' => null,
            'actor_email' => null,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (): array => [
            'actor_id' => null,
            'actor_type' => 'anonymous',
            'actor_name' => null,
        ]);
    }

    public function forModule(string $module): static
    {
        return $this->state(fn (): array => ['module' => $module]);
    }

    public function forAction(string $action): static
    {
        return $this->state(fn (): array => ['action' => $action]);
    }

    public function forResult(string $result): static
    {
        return $this->state(fn (): array => ['result' => $result]);
    }

    public function occurredAt(\DateTimeInterface $at): static
    {
        return $this->state(fn (): array => [
            'occurred_at' => Carbon::instance($at),
        ]);
    }
}
