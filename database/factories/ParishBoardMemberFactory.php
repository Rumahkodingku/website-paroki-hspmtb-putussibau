<?php

namespace Database\Factories;

use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParishBoardMember>
 */
class ParishBoardMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parish_board_period_id' => ParishBoardPeriod::factory(),
            'name' => fake()->name(),
            'position' => fake()->randomElement(['Ketua', 'Wakil Ketua', 'Sekretaris', 'Bendahara']),
            'photo_media_id' => null,
            'photo_alt' => null,
            'description' => null,
            'sort_order' => 0,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function position(string $position): static
    {
        return $this->state(fn (): array => ['position' => $position]);
    }
}
