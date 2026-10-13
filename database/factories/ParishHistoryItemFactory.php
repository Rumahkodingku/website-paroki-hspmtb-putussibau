<?php

namespace Database\Factories;

use App\Models\ParishHistoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ParishHistoryItem>
 *
 * A year-only, published item by default: that is the common shape a parish
 * actually has, and it keeps the "year without a date" case the default one.
 */
class ParishHistoryItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'year' => (int) fake()->numberBetween(1900, (int) date('Y')),
            'date' => null,
            'description' => '<p>'.fake()->paragraph(2).'</p>',
            'media_id' => null,
            'image_alt' => null,
            'sort_order' => 0,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function dated(\DateTimeInterface $date): static
    {
        return $this->state(fn (): array => [
            'year' => (int) Carbon::instance($date)->year,
            'date' => Carbon::instance($date)->toDateString(),
        ]);
    }

    public function withYear(int $year): static
    {
        return $this->state(fn (): array => ['year' => $year, 'date' => null]);
    }

    public function ordered(int $sortOrder): static
    {
        return $this->state(fn (): array => ['sort_order' => $sortOrder]);
    }
}
