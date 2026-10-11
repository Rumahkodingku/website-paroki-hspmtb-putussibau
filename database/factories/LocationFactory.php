<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Location>
 *
 * Values are invented placeholders. No test should assert on the literal
 * contents, and the seeders never use this factory. AGENTS.md forbids inventing
 * parish data, and a factory that produced a plausible-looking stasi roster
 * would be exactly that.
 *
 * @see docs/DECISIONS.md D-34
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => LocationType::GerejaParoki,
            'address' => fake()->address(),
            // Null by default rather than a random coordinate. A random pair is a
            // plausible-looking map link pointing at somewhere impossible, and
            // the tests that do care about coordinates ask for them explicitly.
            'latitude' => null,
            'longitude' => null,
            'maps_url' => null,
            'notes' => null,
            'is_active' => true,
        ];
    }

    /**
     * A location the parish has switched off.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * A location with nothing to build a map link from.
     */
    public function withoutMaps(): static
    {
        return $this->state(fn (): array => [
            'latitude' => null,
            'longitude' => null,
            'maps_url' => null,
        ]);
    }

    /**
     * A location placed on the map, with no hand-written URL.
     *
     * This is the case that exercises Location::mapsUrl() falling back to the
     * coordinates. The default pair is Putussibau's approximate position and is
     * a fixture, not a surveyed coordinate.
     */
    public function withCoordinates(float $latitude = -0.0293, float $longitude = 111.7686): static
    {
        return $this->state(fn (): array => [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'maps_url' => null,
        ]);
    }
}
