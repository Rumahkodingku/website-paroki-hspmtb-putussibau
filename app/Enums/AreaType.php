<?php

namespace App\Enums;

/**
 * What kind of place an area is.
 *
 * PRD 9.9 and Lampiran C fix these three values. `wilayah` and `stasi` are
 * top-level; `lingkungan` is the only type that carries a parent, and only a
 * `wilayah` may be that parent (PRD COM-02). The rule is enforced in
 * AreaValidationRules rather than here, because a type knows its own name and
 * not its parent.
 */
enum AreaType: string
{
    /** A district-level area that contains lingkungan. */
    case Wilayah = 'wilayah';

    /** A neighbourhood-level area under a wilayah. */
    case Lingkungan = 'lingkungan';

    /** A station with its own community, subordinate to the parish church. */
    case Stasi = 'stasi';

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
     * The key is `area.area_types.*`: D-57 moved the area copy out
     * of `komunitas.php` when the module stopped being part of Komunitas, and
     * leaving this pointing at the old group would ship the raw key — literally
     * "lingkungan" — to React as `type_label`.
     *
     * @see docs/DECISIONS.md D-57
     */
    public function label(): string
    {
        return (string) __('area.area_types.'.$this->value);
    }
}
