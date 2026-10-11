<?php

namespace App\Enums;

use ValueError;

/**
 * The day a recurring mass is celebrated, on a weekly cycle.
 *
 * 0 is Sunday and 6 is Saturday, per PRD Lampiran C.
 *
 * These values are deliberately the same as Carbon's dayOfWeek, which is also
 * 0 = Sunday. NextMassResolver subtracts one from the other, so the two have to
 * agree: an off-by-one here moves "the next mass" by a whole day while every
 * test around it still looks correct. If Carbon's numbering ever changes, this
 * is the file that breaks first.
 *
 * An int-backed enum rather than a string one because the column is
 * unsignedTinyInteger. It is still cast on the model, so the rest of the
 * application reads a case, not an integer.
 */
enum DayOfWeek: int
{
    case Minggu = 0;
    case Senin = 1;
    case Selasa = 2;
    case Rabu = 3;
    case Kamis = 4;
    case Jumat = 5;
    case Sabtu = 6;

    /**
     * Every day, Sunday first — the order the public schedule groups them in.
     *
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * A day from user input, or null when it is not a day.
     *
     * Unlike from(), which throws, this returns null for anything outside 0..6.
     * A public page filter reads a query string, and /jadwal-misa?hari=99 should
     * show the full schedule rather than return a 422 (roadmap 5.5).
     */
    public static function tryFromInt(int $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * A day from a value already known to be in range.
     *
     * @throws ValueError when the value is not 0..6
     */
    public static function fromInt(int $value): self
    {
        return self::from($value);
    }

    public function label(): string
    {
        return (string) __('mass-schedule.days.'.$this->value);
    }
}
