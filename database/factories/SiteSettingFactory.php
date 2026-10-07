<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Values are invented, not parish data. Nothing in the test suite should
     * ever assert on the literal contents of a generated value, and the seeders
     * do not use this factory.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
            'group' => fake()->randomElement(array_keys((array) config('site-settings.groups'))),
        ];
    }

    /**
     * Persist a specific key, which is what most settings tests need.
     */
    public function keyed(string $key, ?string $value = null): static
    {
        return $this->state(fn (): array => [
            'key' => $key,
            'value' => $value,
            'group' => $this->groupFor($key),
        ]);
    }

    /**
     * Find which configured group a key belongs to, or null when unknown.
     */
    private function groupFor(string $key): ?string
    {
        foreach ((array) config('site-settings.groups') as $group => $keys) {
            if (in_array($key, $keys, true)) {
                return $group;
            }
        }

        return null;
    }
}
