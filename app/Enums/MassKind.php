<?php

namespace App\Enums;

/**
 * Which of the two schedules a resolved next mass came from.
 *
 * MASS-08 step 4 breaks a tie by preferring a special mass, so "which kind was
 * this" is part of the answer rather than something the caller can read off a
 * null id. It is also what lets the public card label the occasion differently
 * from a weekly mass.
 */
enum MassKind: string
{
    /** From mass_schedules: a recurring weekly mass. */
    case Rutin = 'rutin';

    /** From special_masses: a one-off mass on a specific date. */
    case Khusus = 'khusus';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
