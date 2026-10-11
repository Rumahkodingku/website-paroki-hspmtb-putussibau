<?php

namespace App\Services;

use App\Enums\MassKind;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\SpecialMass;
use App\Support\NextMass;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Answers "which mass happens next" (PRD MASS-08).
 *
 * Three design decisions are load-bearing here and worth stating before the
 * algorithm, because each of them is a way this class could be wrong while
 * still looking right:
 *
 * 1. It never reads the HTTP request. ARCHITECTURE.md Part A section 5 says a
 *    service holds reusable logic and not request state, and
 *    tests/Unit/ArchitectureTest.php enforces it. The current time arrives as a
 *    parameter instead, which is also what makes the AC-MASS-2 boundary tests
 *    possible without waiting for the clock.
 *
 * 2. It compares everything in the display timezone, then returns UTC. "Today"
 *    and "has this hour passed" are wall-clock questions about WIB, so asking
 *    them in UTC would put every mass that starts after 07:00 WIB on the wrong
 *    day. The candidate that leaves is converted back, because stored datetimes
 *    are UTC (D-01).
 *
 * 3. A recurring mass's start_time is a wall clock and is never treated as a
 *    date. It is combined with a calendar date by string, not by casting to a
 *    datetime and adding an offset, which is what turns 07.30 into 00.30.
 *
 * @see docs/DECISIONS.md D-01, D-02
 * @see docs/PRD.md 7.3 MASS-08
 */
class NextMassResolver
{
    /**
     * The soonest mass still to come, or null when there is none.
     *
     * @param  int|null  $locationId  restrict to one location; null searches all
     * @param  DateTimeInterface|null  $now  the moment to measure from
     */
    public function resolve(?int $locationId = null, ?DateTimeInterface $now = null): ?NextMass
    {
        $moment = $this->moment($now);

        $candidates = [
            ...$this->regularCandidates($moment, $locationId),
            ...$this->specialCandidates($locationId),
        ];

        if ($candidates === []) {
            return null;
        }

        usort($candidates, function (NextMass $a, NextMass $b): int {
            // MASS-08 step 4: earliest wins, and a special mass wins a tie.
            // Ordered so that a stable comparison survives: without the second
            // clause two equal masses would compare equal and usort would keep
            // whichever the query happened to return first.
            return [$a->startsAt->getTimestamp(), $a->kind === MassKind::Khusus ? 0 : 1]
                <=> [$b->startsAt->getTimestamp(), $b->kind === MassKind::Khusus ? 0 : 1];
        });

        return $candidates[0];
    }

    /**
     * The same answer scoped to one location, for the public filter.
     *
     * A location with no mass ahead of now yields null rather than a fallback
     * to another location: a visitor who filtered to a stasi and sees a mass
     * from the parish church has been shown something that is not at the place
     * they asked about.
     */
    public function resolveFor(Location $location, ?DateTimeInterface $now = null): ?NextMass
    {
        return $this->resolve($location->getKey(), $now);
    }

    /**
     * Now, in the timezone the parish's calendar is written in.
     *
     * setTestNow() is honoured either way: an explicit $now wins, and without
     * one now() returns whatever the test has frozen. Passing the same instant
     * in two ways has to give the same answer, which is what lets the resolver
     * be driven both ways in the boundary tests.
     */
    private function moment(?DateTimeInterface $now): CarbonImmutable
    {
        $moment = $now === null
            ? CarbonImmutable::now()
            : CarbonImmutable::instance($now);

        return $moment->setTimezone($this->displayTimezone());
    }

    /**
     * Every recurring mass with an occurrence at or after the given moment.
     *
     * At most seven days ahead by construction, so the whole set is small and
     * there is nothing to paginate.
     *
     * @return list<NextMass>
     */
    private function regularCandidates(CarbonImmutable $moment, ?int $locationId): array
    {
        $schedules = MassSchedule::query()
            ->active()
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->with('location')
            ->get();

        $candidates = [];

        foreach ($schedules as $schedule) {
            $candidates[] = NextMass::regular(
                startsAt: $this->nextOccurrence($schedule, $moment),
                name: $schedule->name,
                location: $schedule->location,
                massScheduleId: $schedule->getKey(),
            );
        }

        return $candidates;
    }

    /**
     * Every one-off mass still to come.
     *
     * No time arithmetic here: start_at is a real datetime, already UTC, and
     * scopeUpcoming has already compared it against now. The only reason this
     * method exists is so the two candidate sources read symmetrically.
     *
     * @return list<NextMass>
     */
    private function specialCandidates(?int $locationId): array
    {
        $masses = SpecialMass::query()
            ->upcoming()
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->with('location')
            ->get();

        $candidates = [];

        foreach ($masses as $mass) {
            $candidates[] = NextMass::special(
                startsAt: CarbonImmutable::instance($mass->start_at),
                title: $mass->title,
                location: $mass->location,
                specialMassId: $mass->getKey(),
            );
        }

        return $candidates;
    }

    /**
     * When a weekly recurrence next happens, at or after $moment.
     *
     * Two steps, and the order matters:
     *
     *   1. find the next calendar date whose weekday matches. The modulo handles
     *      wrapping past Saturday, which is why it is + 7 rather than a bare
     *      subtraction — without it a Sunday mass looked for from a Monday
     *      would compute -1 days.
     *
     *   2. if that date is today and the hour has already passed, go to the same
     *      weekday next week.
     *
     * The result is >= $moment by construction, which is what makes "starts at
     * exactly this minute" count as upcoming rather than having moved on.
     */
    private function nextOccurrence(MassSchedule $schedule, CarbonImmutable $moment): CarbonImmutable
    {
        // day_of_week is 0 = Sunday and so is Carbon's dayOfWeek, so the two can
        // be subtracted directly. See App\Enums\DayOfWeek.
        $daysAhead = ($schedule->day_of_week->value - $moment->dayOfWeek + 7) % 7;

        $candidate = $moment
            ->startOfDay()
            ->addDays($daysAhead)
            ->setTimeFromTimeString($schedule->start_time);

        if ($candidate < $moment) {
            $candidate = $candidate->addWeek();
        }

        return $candidate->setTimezone('UTC');
    }

    private function displayTimezone(): string
    {
        return (string) config('app.display_timezone');
    }
}
