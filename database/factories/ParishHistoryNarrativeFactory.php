<?php

namespace Database\Factories;

use App\Models\ParishHistoryNarrative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParishHistoryNarrative>
 *
 * Sample text only — never a real parish value.
 */
class ParishHistoryNarrativeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'narrative' => '<p>'.fake()->paragraph(4).'</p>',
        ];
    }
}
