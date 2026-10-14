<?php

namespace App\Support;

/**
 * One place that turns a typed phone number into a tel: or wa.me link.
 *
 * PRD KON-01 requires the same normalization for the Contact page, and SVC-07
 * needs it for a service's contact buttons, so it lives here rather than being
 * re-derived in each resource. The rule: keep the digits, drop everything a
 * person types around them (spaces, dashes, parentheses, dots, a leading plus),
 * and express an Indonesian number in international form.
 *
 *   0812-3456-7890   -> 628123456789
 *   +62 812 3456 789   -> 628123456789
 *   81234567890        -> 628123456789
 *
 * Anything that does not reduce to a plausible number returns null, and a null
 * contact means no button is drawn — never a link pointing nowhere. Emails are
 * out of scope; this is only for numbers.
 *
 * @see docs/PRD.md KON-01, SVC-07
 * @see docs/DECISIONS.md D-54
 */
final class PhoneNumber
{
    /**
     * Shortest and longest digit strings treated as a real number, after
     * normalization. An Indonesian mobile is 62 + 9..12 digits; the window is
     * generous to allow landlines and short extensions without accepting junk.
     */
    private const MIN_DIGITS = 9;

    private const MAX_DIGITS = 15;

    /**
     * Normalize to international digits without a plus, or null.
     *
     * @return string|null e.g. "628123456789"
     */
    public static function normalize(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            // A national number: replace the trunk zero with the country code.
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            // A mobile typed without its leading zero.
            $digits = '62'.$digits;
        }
        // Anything else is assumed already international (starts with a country
        // code) and is kept as typed.

        if (strlen($digits) < self::MIN_DIGITS || strlen($digits) > self::MAX_DIGITS) {
            return null;
        }

        return $digits;
    }

    /**
     * The number is usable as a contact link.
     */
    public static function isValid(?string $number): bool
    {
        return self::normalize($number) !== null;
    }

    /**
     * A tel: link, or null when the number is not usable.
     *
     * The plus marks the number as international for the phone dialler.
     */
    public static function toTel(?string $number): ?string
    {
        $normalized = self::normalize($number);

        return $normalized === null ? null : 'tel:+'.$normalized;
    }

    /**
     * A wa.me link, or null when the number is not usable.
     *
     * wa.me wants the number without a plus and without the trunk zero, which is
     * exactly what normalize() returns.
     */
    public static function toWhatsApp(?string $number): ?string
    {
        $normalized = self::normalize($number);

        return $normalized === null ? null : 'https://wa.me/'.$normalized;
    }
}
