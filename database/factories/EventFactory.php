<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 *
 * The default is a published activity starting tomorrow at nine in the morning
 * WIB, which is the case a public agenda test wants most often. Every other
 * state is named rather than reached by passing attributes, so a test saying
 * which case it means does not have to know the default.
 *
 * Times are built in Asia/Pontianak and converted to UTC here. A datetime cast
 * formats a DateTimeInterface in *its own* timezone rather than converting it,
 * so a Carbon built from a WIB wall clock and handed straight to the model is
 * stored seven hours early and nothing reports an error.
 *
 * @see docs/DECISIONS.md D-44
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->sentence(3));
        $start = Carbon::now()
            ->addDay()
            ->setTimezone(config('app.display_timezone'))
            ->setTime(9, 0);

        return [
            'event_category_id' => EventCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => null,
            'start_at' => $start->copy()->utc(),
            'end_at' => null,
            'is_all_day' => false,
            // Free text rather than a master location, because EVT-06 says that
            // is the common case. LocationFactory goes in withLocation().
            'location_name' => fake()->sentence(2),
            'location_id' => null,
            'poster_path' => null,
            'poster_alt' => null,
            'status' => EventStatus::Scheduled,
            'is_published' => true,
            'media_id' => null,
        ];
    }

    /**
     * Hidden from the website. Roadmap 8.2: is_published is the everyday
     * control, and delete is the escape hatch.
     */
    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    /**
     * Cancelled but still listed, with a label (EVT-04).
     */
    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => EventStatus::Cancelled]);
    }

    /**
     * Postponed but still listed, with a label.
     */
    public function postponed(): static
    {
        return $this->state(fn (): array => ['status' => EventStatus::Postponed]);
    }

    /**
     * A whole day rather than a time of day.
     *
     * Roadmap 8.1: the admin is not forced to invent a clock, and the .ics
     * writer needs to know so it can emit VALUE=DATE.
     */
    public function allDay(): static
    {
        return $this->state(fn (): array => [
            'is_all_day' => true,
            'start_at' => Carbon::now()
                ->addDay()
                ->setTimezone(config('app.display_timezone'))
                ->startOfDay()
                ->utc(),
            'end_at' => null,
        ]);
    }

    /**
     * Already over. EVT-02: gone from the upcoming list, still reachable
     * through the calendar's past months.
     */
    public function past(): static
    {
        return $this->state(fn (): array => [
            'start_at' => Carbon::now()
                ->subWeek()
                ->setTimezone(config('app.display_timezone'))
                ->setTime(9, 0)
                ->utc(),
        ]);
    }

    /**
     * Pointing at a master location rather than a name.
     *
     * A Factory, not a created record: Eloquent expands a nested factory in a
     * state closure the same way it does in a definition, and calling
     * ->create() here would insert the row twice — once for the state and once
     * again when the event is created.
     */
    public function withLocation(?Location $location = null): static
    {
        return $this->state(fn (): array => [
            'location_id' => $location instanceof Location
                ? $location->getKey()
                : Location::factory(),
            // Kept alongside, so switching the master location off does not lose
            // what the admin typed. EVT-06 says location_id wins on display.
            'location_name' => fake()->sentence(2),
        ]);
    }
}
