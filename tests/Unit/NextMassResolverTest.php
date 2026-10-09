<?php

use App\Enums\DayOfWeek;
use App\Enums\MassKind;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\SpecialMass;
use App\Services\NextMassResolver;
use App\Support\NextMass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NextMassResolver
|--------------------------------------------------------------------------
|
| MASS-08 is the requirement most able to pass a casual look and still be wrong:
| every case below is a boundary where an off-by-one produces a plausible page.
| Sunday 07:31 returning next Sunday instead of "no mass" looks like an answer.
|
| Two properties are asserted throughout, and neither is in the roadmap's own
| list:
|
|   - the timezone of the answer is Asia/Pontianak, and the resolver takes its
|     "now" as a parameter so a test never depends on the machine clock;
|   - start_time is a wall clock. U-9 is the test for D-02: if anyone ever adds
|     a cast to that column, 07:30 becomes 00:30 and U-9 fails even though
|     every other test here still passes.
|
| @see docs/DECISIONS.md D-01, D-02
*/

/**
 * Freeze the clock at a WIB wall-clock time.
 *
 * Written as a helper rather than repeated because getting it wrong is the easy
 * failure: passing a UTC-looking string would make every expectation below off
 * by seven hours and the suite would pass while testing nothing.
 */
function atWib(string $dateTime): void
{
    Carbon::setTestNow(
        Carbon::parse($dateTime, config('app.display_timezone'))->utc()
    );
}

/**
 * The next occurrence, formatted in WIB so a failure message is readable.
 */
function occurrenceOfMass(?NextMass $next): ?string
{
    return $next?->startsAtInDisplayTimezone()->format('D Y-m-d H:i');
}

afterEach(function () {
    Carbon::setTestNow();
});

/*
|--------------------------------------------------------------------------
| U-1 — the next recurring mass
|--------------------------------------------------------------------------
*/

it('picks the soonest recurring mass of the week', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Senin, '06:00')->create();
    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Rabu, '06:00')->create();

    atWib('2026-10-05 08:00'); // Monday

    $next = resolveNextMass();

    expect($next)->not->toBeNull()
        ->and($next->kind)->toBe(MassKind::Rutin)
        ->and(occurrenceOfMass($next))->toBe('Wed 2026-10-07 06:00');
});

/*
|--------------------------------------------------------------------------
| U-2 — "at or after", not "after"
|--------------------------------------------------------------------------
*/

it('keeps a mass starting at exactly this minute', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    atWib('2026-10-04 07:30'); // Sunday, the exact hour

    $next = resolveNextMass();

    expect($next)->not->toBeNull()
        ->and(occurrenceOfMass($next))->toBe('Sun 2026-10-04 07:30');
});

/*
|--------------------------------------------------------------------------
| U-3 — today's hour has passed
|--------------------------------------------------------------------------
*/

it('moves to seven days later once today\'s hour has passed', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    atWib('2026-10-04 07:31'); // one minute past

    $next = resolveNextMass();

    expect($next)->not->toBeNull()
        ->and(occurrenceOfMass($next))->toBe('Sun 2026-10-11 07:30');
});

/*
|--------------------------------------------------------------------------
| The AC-MASS-2 table, roadmap 9.5
|--------------------------------------------------------------------------
*/

it('moves to Sunday 07.30 when it is Sunday 07.29', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    atWib('2026-10-04 07:29');

    expect(occurrenceOfMass(resolveNextMass()))->toBe('Sun 2026-10-04 07:30');
});

it('rolls Sunday 07.30 to the following week when it is Sunday 07.31', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    atWib('2026-10-04 07:31');

    expect(occurrenceOfMass(resolveNextMass()))->toBe('Sun 2026-10-11 07:30');
});

it('does not shift the day across midnight', function () {
    $location = Location::factory()->create();

    // Saturday evening: the next Sunday mass must not land on the Saturday.
    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    atWib('2026-10-03 23:59'); // Saturday, one minute to midnight

    expect(occurrenceOfMass(resolveNextMass()))->toBe('Sun 2026-10-04 07:30');
});

it('rolls a past Saturday evening mass to next week and prefers Sunday over it', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Sabtu, '17:00')->create();
    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    atWib('2026-10-03 23:59'); // Saturday, one minute to midnight

    // The Saturday 17.00 mass ended hours ago, so the Sunday morning one is the
    // answer — and it must be *this* Sunday, not a week out. A resolver that
    // compared weekdays rather than occurrences would get this wrong while
    // looking correct.
    expect(occurrenceOfMass(resolveNextMass()))->toBe('Sun 2026-10-04 07:30');
});

/*
|--------------------------------------------------------------------------
| U-4 — a special mass wins when it is earlier
|--------------------------------------------------------------------------
*/

it('prefers a special mass that starts before the recurring one', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();
    SpecialMass::factory()->for($location)->startingAt(
        Carbon::parse('2026-10-05 06:00', config('app.display_timezone'))->utc()
    )->create();

    atWib('2026-10-04 12:00'); // Sunday midday, so the 07.30 mass is next week

    $next = resolveNextMass();

    expect($next)->not->toBeNull()
        ->and($next->kind)->toBe(MassKind::Khusus)
        ->and(occurrenceOfMass($next))->toBe('Mon 2026-10-05 06:00');
});

/*
|--------------------------------------------------------------------------
| U-5 — the tie goes to the special mass
|--------------------------------------------------------------------------
*/

it('breaks a tie in favour of the special mass', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '09:00')->create();
    SpecialMass::factory()->for($location)->startingAt(
        Carbon::parse('2026-10-04 09:00', config('app.display_timezone'))->utc()
    )->create();

    atWib('2026-10-04 07:00');

    $next = resolveNextMass();

    expect($next)->not->toBeNull()
        ->and($next->kind)->toBe(MassKind::Khusus)
        ->and(occurrenceOfMass($next))->toBe('Sun 2026-10-04 09:00');
});

/*
|--------------------------------------------------------------------------
| U-6 — the location filter
|--------------------------------------------------------------------------
*/

it('restricts candidates to the requested location', function () {
    $wanted = Location::factory()->create();
    $other = Location::factory()->create();

    MassSchedule::factory()->for($wanted)->onDay(DayOfWeek::Senin, '06:00')->create();
    MassSchedule::factory()->for($other)->onDay(DayOfWeek::Selasa, '06:00')->create();

    atWib('2026-10-05 05:00'); // Monday, before the mass

    $next = resolveNextMass($wanted->getKey());

    expect($next)->not->toBeNull()
        ->and($next->location->is($wanted))->toBeTrue()
        ->and(occurrenceOfMass($next))->toBe('Mon 2026-10-05 06:00');
});

it('returns null for a location with no mass ahead of it, rather than another location\'s', function () {
    $wanted = Location::factory()->create();
    Location::factory()->create()->massSchedules()->create([
        'day_of_week' => DayOfWeek::Selasa,
        'start_time' => '06:00:00',
        'name' => 'Misa di tempat lain',
    ]);

    atWib('2026-10-05 05:00'); // Monday

    expect(resolveNextMass($wanted->getKey()))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| U-7 — no candidates
|--------------------------------------------------------------------------
*/

it('returns null when nothing is scheduled', function () {
    atWib('2026-10-05 08:00');

    expect(resolveNextMass())->toBeNull();
});

it('returns null when every schedule is inactive', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->inactive()->onDay(DayOfWeek::Senin, '06:00')->create();

    atWib('2026-10-05 08:00');

    expect(resolveNextMass())->toBeNull();
});

it('ignores an inactive location\'s masses (MASS-07)', function () {
    $location = Location::factory()->inactive()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Senin, '06:00')->create();

    atWib('2026-10-05 08:00');

    expect(resolveNextMass())->toBeNull();
});

it('ignores a special mass that has already passed (MASS-02)', function () {
    $location = Location::factory()->create();

    SpecialMass::factory()->for($location)->past()->create();

    atWib('2026-10-05 08:00');

    expect(resolveNextMass())->toBeNull();
});

/*
|--------------------------------------------------------------------------
| U-9 — D-02: start_time is a wall clock, never converted
|--------------------------------------------------------------------------
*/

it('reads 07.30 as 07.30 WIB rather than shifting it by the UTC offset', function () {
    $location = Location::factory()->create();

    MassSchedule::factory()->for($location)->onDay(DayOfWeek::Minggu, '07:30')->create();

    atWib('2026-10-04 05:00'); // well before the mass, in WIB

    $next = resolveNextMass();

    expect($next)->not->toBeNull()
        // 00.30 here would mean the column had been cast to a datetime and the
        // UTC offset applied, which is the failure D-02 exists to prevent.
        ->and(occurrenceOfMass($next))->toBe('Sun 2026-10-04 07:30')
        ->and($next->startsAtInDisplayTimezone()->format('H:i'))->toBe('07:30');
});

it('stores start_time as the wall clock it was given', function () {
    $location = Location::factory()->create();

    $schedule = MassSchedule::factory()->for($location)->onDay(DayOfWeek::Senin, '07:30')->create();

    expect($schedule->fresh()->start_time)->toBe('07:30:00')
        ->and($schedule->fresh()->displayTime())->toBe('07.30 WIB');
});

/*
|--------------------------------------------------------------------------
| U-10 — the calendar weekday numbering
|--------------------------------------------------------------------------
*/

it('reads day_of_week with the same numbering as the calendar (0 = Minggu)', function (): void {
    // Sunday must be 0 for this table to hold. It is the assumption the modulo
    // in the resolver rests on, and getting it wrong shifts every answer a day
    // while leaving the resolver itself looking correct.
    expect(DayOfWeek::Minggu->value)->toBe(0)
        ->and(DayOfWeek::Sabtu->value)->toBe(6)
        ->and(Carbon::parse('2026-10-04', config('app.display_timezone'))->dayOfWeek)->toBe(0)
        ->and(Carbon::parse('2026-10-03', config('app.display_timezone'))->dayOfWeek)->toBe(6);
});

/**
 * Resolve through the container, so the test exercises the wiring the
 * controller actually uses rather than a hand-built instance.
 */
function resolveNextMass(?int $locationId = null): ?NextMass
{
    return app(NextMassResolver::class)->resolve($locationId);
}
