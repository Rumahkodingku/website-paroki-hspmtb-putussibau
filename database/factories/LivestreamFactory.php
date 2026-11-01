<?php

namespace Database\Factories;

use App\Enums\LivestreamBroadcastStatus;
use App\Enums\LivestreamEditorialStatus;
use App\Models\Livestream;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Livestream>
 *
 * The default is a draft scheduled stream: not public, which is the state a
 * record is actually created in. Every other case is a named state, so a test
 * that says what it means does not have to know the default.
 */
class LivestreamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // word() is typed as string, unlike words(), whose signature allows
        // array|string depending on its second argument.
        $title = Str::title(
            fake()->word().' '.fake()->word().' '.fake()->word(),
        );

        $videoId = Str::random(11);

        return [
            'title' => $title,
            'description' => fake()->sentence(12),
            'youtube_video_id' => $videoId,
            'youtube_url' => 'https://www.youtube.com/watch?v='.$videoId,
            'location_id' => null,
            'category' => null,
            'status' => LivestreamEditorialStatus::Draft,
            'broadcast_status' => LivestreamBroadcastStatus::Scheduled,
            'live_slot' => null,
            'scheduled_at' => null,
            'started_at' => null,
            'ended_at' => null,
            'recorded_at' => null,
            'is_recording_available' => false,
            'published_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Point the record at a master-data location.
     */
    public function forLocation(Location $location): static
    {
        return $this->state(fn (): array => [
            'location_id' => $location->getKey(),
        ]);
    }

    /**
     * A stream an admin has published but not started.
     */
    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => LivestreamEditorialStatus::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * The one live stream. The unique live_slot means a test must not create
     * two of these at once; that collision is the point of the constraint.
     */
    public function live(): static
    {
        return $this->state(fn (): array => [
            'status' => LivestreamEditorialStatus::Published,
            'broadcast_status' => LivestreamBroadcastStatus::Live,
            'live_slot' => 1,
            'published_at' => now(),
            'started_at' => now(),
        ]);
    }

    /**
     * A finished stream. The replay is not assumed to exist; chain
     * recordingAvailable() when the test needs a public archive item.
     */
    public function ended(): static
    {
        return $this->state(fn (): array => [
            'status' => LivestreamEditorialStatus::Published,
            'broadcast_status' => LivestreamBroadcastStatus::Ended,
            'live_slot' => null,
            'published_at' => now(),
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'recorded_at' => now(),
        ]);
    }

    /**
     * An ended stream whose YouTube replay an admin has verified.
     */
    public function recordingAvailable(): static
    {
        return $this->state(fn (): array => [
            'ended_at' => now(),
            'recorded_at' => now(),
            'is_recording_available' => true,
        ]);
    }

    /**
     * A video that cannot be played.
     */
    public function unavailable(): static
    {
        return $this->state(fn (): array => [
            'status' => LivestreamEditorialStatus::Published,
            'broadcast_status' => LivestreamBroadcastStatus::Unavailable,
            'live_slot' => null,
            'published_at' => now(),
        ]);
    }
}
