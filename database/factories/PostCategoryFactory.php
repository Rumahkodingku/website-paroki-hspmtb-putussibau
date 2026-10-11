<?php

namespace Database\Factories;

use App\Models\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PostCategory>
 *
 * Values are invented placeholders. No test should assert on the literal
 * contents, and the seeders never use this factory — PostCategorySeeder writes
 * the four categories PRD 9.6 names. AGENTS.md forbids inventing parish data.
 *
 * @see docs/DECISIONS.md D-43
 */
class PostCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // sentence() returns a string; words() is typed array|string even with
        // $asText, so the analyser cannot narrow it and joining it would only
        // move the error.
        $name = Str::title(fake()->unique()->sentence(2));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => 0,
        ];
    }
}
