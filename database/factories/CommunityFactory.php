<?php

namespace Database\Factories;

use App\Enums\CommunityStatus;
use App\Models\Community;
use App\Models\CommunityCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Community>
 *
 * The default is a draft community still forming: not public, which is the state
 * a community is actually created in. Every other case is a named state, so a
 * test that says what it means does not have to know the default.
 */
class CommunityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // word() is typed as string, unlike words(), whose signature allows
        // array|string depending on its second argument.
        $name = Str::title(
            fake()->unique()->word().' '.fake()->word().' '.fake()->word(),
        );

        return [
            'community_category_id' => CommunityCategory::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'logo_media_id' => null,
            'logo_alt' => null,
            'thumbnail_media_id' => null,
            'thumbnail_alt' => null,
            'description' => fake()->sentence(12),
            'vision' => null,
            'mission' => null,
            'meeting_schedule' => null,
            'meeting_location' => null,
            'eligibility' => null,
            'joining_info' => null,
            'contact_name' => null,
            'contact_phone' => null,
            'show_contact' => false,
            // D-59: the community's own links. Null by default so a test that
            // forgets them does not accidentally assert the public page shows a
            // social section.
            'facebook' => null,
            'instagram' => null,
            'tiktok' => null,
            'youtube' => null,
            'website' => null,
            // A new community starts forming; only active + published is public.
            'status' => CommunityStatus::Forming,
            'is_published' => false,
            'published_at' => null,
        ];
    }

    public function forming(): static
    {
        return $this->state(fn (): array => [
            'status' => CommunityStatus::Forming,
            'is_published' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => CommunityStatus::Active]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => CommunityStatus::Inactive]);
    }

    /**
     * An active, published community: the only shape the public site shows.
     */
    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => CommunityStatus::Active,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * A community with a published contact, for the privacy tests both ways.
     */
    public function withContact(string $phone = '628123456789'): static
    {
        return $this->state(fn (): array => [
            'contact_name' => fake()->name(),
            'contact_phone' => $phone,
            'show_contact' => true,
        ]);
    }

    /**
     * A community with every social column filled, for the public detail page.
     *
     * Reserve example.com rather than a real platform domain: these are test
     * rows, and a test that asserts on facebook.com is one DNS hiccup away from
     * asserting on somebody's actual page.
     */
    public function withSocialLinks(): static
    {
        return $this->state(fn (): array => [
            'facebook' => 'https://facebook.com/contoh-komunitas',
            'instagram' => 'https://instagram.com/contoh-komunitas',
            'tiktok' => 'https://tiktok.com/@contoh-komunitas',
            'youtube' => 'https://youtube.com/@contoh-komunitas',
            'website' => 'https://contoh-komunitas.test',
        ]);
    }
}
