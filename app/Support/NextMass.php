<?php

namespace App\Support;

use App\Enums\MassKind;
use App\Models\Location;
use Carbon\CarbonImmutable;

/**
 * The answer to "which mass is next", already resolved.
 *
 * A value rather than a query result: the resolver turns several candidate rows
 * into one decision, and everything downstream — the resource, the public card,
 * the tests — reads the decision instead of repeating the comparison. Building
 * it as a DTO is what keeps "which kind won" and "when does it start" together,
 * because MASS-08 step 4 needs both to break a tie.
 *
 * startsAt is UTC like everything else stored as a datetime (D-01). It is
 * converted to app.display_timezone once, at the edge, for display.
 *
 * @see docs/PRD.md 7.3 MASS-08
 */
final readonly class NextMass
{
    public function __construct(
        public MassKind $kind,
        public CarbonImmutable $startsAt,
        public string $name,
        public Location $location,
        public ?int $massScheduleId = null,
        public ?int $specialMassId = null,
    ) {}

    /**
     * Build the candidate for a recurring mass.
     *
     * @param  CarbonImmutable  $startsAt  the next occurrence, in UTC
     */
    public static function regular(
        CarbonImmutable $startsAt,
        string $name,
        Location $location,
        int $massScheduleId,
    ): self {
        return new self(
            kind: MassKind::Rutin,
            startsAt: $startsAt,
            name: $name,
            location: $location,
            massScheduleId: $massScheduleId,
            specialMassId: null,
        );
    }

    /**
     * Build the candidate for a one-off mass.
     */
    public static function special(
        CarbonImmutable $startsAt,
        string $title,
        Location $location,
        int $specialMassId,
    ): self {
        return new self(
            kind: MassKind::Khusus,
            startsAt: $startsAt,
            name: $title,
            location: $location,
            massScheduleId: null,
            specialMassId: $specialMassId,
        );
    }

    public function isRegular(): bool
    {
        return $this->kind === MassKind::Rutin;
    }

    public function isSpecial(): bool
    {
        return $this->kind === MassKind::Khusus;
    }

    /**
     * The moment, already in the display timezone.
     *
     * Convenience for the resource so the conversion is written once. Nothing
     * else in the application should be formatting this value.
     */
    public function startsAtInDisplayTimezone(): CarbonImmutable
    {
        return $this->startsAt->setTimezone(config('app.display_timezone'));
    }
}
