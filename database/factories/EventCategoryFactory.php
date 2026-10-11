<?php

namespace Database\Factories;

use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventCategory>
 *
 * The colour is drawn from a fixed list of hex values rather than generated.
 * A random six-digit string is a random colour — it can come out as near-white
 * on a near-white calendar, which is invisible, and it is not re-themeable.
 * The seeded five are the palette the parish actually gets.
 *
 * @see docs/DECISIONS.md D-44
 */
class EventCategoryFactory extends Factory
{
    /**
     * The palette EventCategorySeeder writes. Duplicated here rather than
     * imported because a seeder is not a class a factory should depend on.
     *
     * @var list<string>
     */
    private const PALETTE = ['#2E7D32', '#1976D2', '#F57C00', '#7B1FA2', '#757575'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->sentence(2));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->randomElement(self::PALETTE),
        ];
    }
}
