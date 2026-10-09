<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\SpecialMass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecialMass>
 *
 * The default starts a week out, which keeps a mass in the future relative to
 * the moment the factory is called — the case MASS-02 talks about. Use past()
 * explicitly to build the counter-case.
 *
 * @see docs/DECISIONS.md D-34
 */
class SpecialMassFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addWeek();

        return [
            'location_id' => Location::factory(),
            'title' => fake()->randomElement(['Misa Paskah', 'Misa Natal', 'Peringatan Kiwa', 'Misa Arwah']).' '.fake()->year(),
            'description' => null,
            'start_at' => $start->copy()->setTime(9, 0),
            'end_at' => null,
            'notes' => null,
            'is_active' => true,
        ];
    }

    /**
     * A mass that has already happened.
     *
     * MASS-02: still stored, hidden from the public tab.
     */
    public function past(): static
    {
        return $this->state(fn (): array => [
            'start_at' => now()->subWeek()->setTime(9, 0),
        ]);
    }

    /**
     * A mass in the future at an explicit moment.
     */
    public function startingAt(\DateTimeInterface $when): static
    {
        return $this->state(fn (): array => ['start_at' => $when]);
    }

    /**
     * A mass the parish has switched off.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
