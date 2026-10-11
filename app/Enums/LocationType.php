<?php

namespace App\Enums;

/**
 * What kind of place a mass is celebrated at.
 *
 * PRD Lampiran C fixes these four values, and they are also the column type on
 * `locations` (see the create_mass_schedule_tables migration), so adding a case
 * here changes the database too rather than leaving a value the column rejects.
 *
 * A chapel inside a village is not a stasi: the distinction matters to the
 * parish administration, so Lainnya exists for anything that is none of the
 * three named kinds rather than forcing it into one.
 */
enum LocationType: string
{
    /** The main church. */
    case GerejaParoki = 'gereja_paroki';

    /** A church with its own community, subordinate to the parish church. */
    case Stasi = 'stasi';

    /** A smaller oratory without a full community. */
    case Kapel = 'kapel';

    /** Anything that is none of the three above. */
    case Lainnya = 'lainnya';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The interface label, read from the language file rather than hardcoded.
     *
     * The seeder and the factories must never call this to invent a parish
     * value; it exists for the interface, which is the only place a parish name
     * may be shown.
     */
    public function label(): string
    {
        return (string) __('mass-schedule.types.'.$this->value);
    }
}
