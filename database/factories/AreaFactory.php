<?php

namespace Database\Factories;

use App\Enums\AreaType;
use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Area>
 *
 * The default is a wilayah: a top-level area with no parent, which is the only
 * shape a test can create without first creating another area. `lingkungan()`
 * brings its own wilayah parent so a test that needs the valid two-level shape
 * does not have to assemble it by hand.
 */
class AreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // word() is typed as string, unlike words(), whose signature allows
        // array|string depending on its second argument.
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => AreaType::Wilayah,
            'description' => null,
            'address' => null,
            'maps_url' => null,
            'latitude' => null,
            'longitude' => null,
            'leader_name' => null,
            'contact_phone' => null,
            'show_contact' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function wilayah(): static
    {
        return $this->state(fn (): array => [
            'type' => AreaType::Wilayah,
            'parent_id' => null,
        ]);
    }

    /**
     * A neighbourhood under a wilayah. The parent is created here, because a
     * lingkungan without a wilayah parent is not a valid area (PRD COM-02).
     */
    public function lingkungan(): static
    {
        return $this->state(fn (): array => [
            'type' => AreaType::Lingkungan,
            'parent_id' => Area::factory()->wilayah(),
        ]);
    }

    public function stasi(): static
    {
        return $this->state(fn (): array => [
            'type' => AreaType::Stasi,
            'parent_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * An area that knows where it is, for the public detail page.
     *
     * The coordinates are a real decimal with seven places, because that is what
     * a cast to decimal:7 round-trips and what Area::mapsUrl() splices into the
     * link. contoh.test rather than a real platform, so a test never asserts on
     * somebody's map pin.
     */
    public function withLocation(): static
    {
        return $this->state(fn (): array => [
            'address' => 'Gereja Stasi Contoh, Jalan Contoh No. 1, Putussibau',
            'latitude' => '-0.4312900',
            'longitude' => '111.4935200',
        ]);
    }

    /**
     * An area with a leader and a published phone: the shape the public detail
     * page renders when show_contact is on.
     */
    public function withContact(string $phone = '628123456789'): static
    {
        return $this->state(fn (): array => [
            'leader_name' => fake()->name(),
            'contact_phone' => $phone,
            'show_contact' => true,
        ]);
    }
}
