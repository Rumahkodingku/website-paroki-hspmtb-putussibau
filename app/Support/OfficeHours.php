<?php

namespace App\Support;

/**
 * The weekly secretariat office hours, stored as JSON in site_settings.
 *
 * One setting holds the whole week: seven days, each open or closed with an
 * opening and a closing time. The day keys are English because they are data;
 * the labels are Indonesian because they are for people.
 *
 * This class owns three things so no call site has to: the canonical day order,
 * the normalisation of whatever is in the column — new JSON, a legacy free-text
 * string, or nothing — and the display formatting the public pages read.
 *
 * @see docs/DECISIONS.md D-68
 */
final class OfficeHours
{
    /**
     * Monday first, the order the editor and the public page both use.
     *
     * @var array<string, string>
     */
    public const DAYS = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu',
    ];

    /**
     * A week with every day closed and no times.
     *
     * The editor's starting point and the value it falls back to when nothing
     * is stored yet.
     *
     * @return array<string, array{is_open: bool, open_time: string|null, close_time: string|null}>
     */
    public static function emptyWeek(): array
    {
        $week = [];

        foreach (array_keys(self::DAYS) as $day) {
            $week[$day] = [
                'is_open' => false,
                'open_time' => null,
                'close_time' => null,
            ];
        }

        return $week;
    }

    /**
     * The stored value as a full week, or null when it is not a structured one.
     *
     * The service hands back either an array (new JSON) or a string (a value
     * written before this editor existed). Only the array is normalised; a
     * legacy string is deliberately left to legacyText() so it is never lost.
     *
     * An open day without both times is treated as closed rather than formatted
     * as a broken line. Validation prevents it being stored that way, so this is
     * a guard for hand-edited or corrupt rows.
     *
     * @return array<string, array{is_open: bool, open_time: string|null, close_time: string|null}>|null
     */
    public static function normalize(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $week = self::emptyWeek();

        foreach (array_keys(self::DAYS) as $day) {
            $entry = $value[$day] ?? null;

            if (! is_array($entry)) {
                continue;
            }

            $open = self::time($entry['open_time'] ?? null);
            $close = self::time($entry['close_time'] ?? null);
            $isOpen = (bool) ($entry['is_open'] ?? false) && $open !== null && $close !== null;

            $week[$day] = [
                'is_open' => $isOpen,
                'open_time' => $open,
                'close_time' => $close,
            ];
        }

        return $week;
    }

    /**
     * The legacy free-text value, or null when there is nothing textual.
     */
    public static function legacyText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = trim($value);

        return $text === '' ? null : $text;
    }

    /**
     * The schedule as display text, or null when there is nothing to show.
     *
     * A legacy string is returned unchanged, so a parish that had free text
     * keeps seeing it until an admin re-enters the schedule in the editor. A
     * structured week is grouped: consecutive open days with identical times
     * become one line, and each closed day is listed on its own. A week with no
     * open day returns null, because a schedule that says "closed" seven times
     * is not worth a heading.
     */
    public static function format(mixed $value): ?string
    {
        $legacy = self::legacyText($value);

        if ($legacy !== null) {
            return $legacy;
        }

        $week = self::normalize($value);

        if ($week === null) {
            return null;
        }

        // A week where nothing is open has no schedule to show.
        foreach ($week as $entry) {
            if ($entry['is_open']) {
                return implode("\n", self::lines($week));
            }
        }

        return null;
    }

    /**
     * @param  array<string, array{is_open: bool, open_time: string|null, close_time: string|null}>  $week
     * @return list<string>
     */
    private static function lines(array $week): array
    {
        $days = array_keys(self::DAYS);
        $count = count($days);
        $lines = [];
        $index = 0;

        while ($index < $count) {
            $day = $days[$index];
            $entry = $week[$day];

            if (! $entry['is_open']) {
                $lines[] = self::DAYS[$day].': Tutup';
                $index++;

                continue;
            }

            // Extend the range while the next day is open with the same times.
            $end = $index;

            while (
                $end + 1 < $count
                && $week[$days[$end + 1]]['is_open']
                && $week[$days[$end + 1]]['open_time'] === $entry['open_time']
                && $week[$days[$end + 1]]['close_time'] === $entry['close_time']
            ) {
                $end++;
            }

            $startLabel = self::DAYS[$day];
            $endLabel = self::DAYS[$days[$end]];
            $range = $startLabel === $endLabel ? $startLabel : $startLabel.'–'.$endLabel;

            $lines[] = $range.': '.self::clock($entry['open_time']).'–'.self::clock($entry['close_time']).' WIB';

            $index = $end + 1;
        }

        return $lines;
    }

    /**
     * "08:00" as "08.00", the Indonesian convention, or empty for null.
     */
    private static function clock(?string $time): string
    {
        return $time === null ? '' : str_replace(':', '.', $time);
    }

    /**
     * A "HH:mm" time, or null when the value is not one.
     */
    private static function time(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return preg_match('/^\d{2}:\d{2}$/', $value) === 1 ? $value : null;
    }
}
