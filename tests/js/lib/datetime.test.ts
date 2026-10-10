import { describe, expect, it } from 'vite-plus/test';

/*
 * lib/datetime.ts.
 *
 * The one file in this module where a wrong answer still looks like a right one.
 * `formatWibTime` in particular exists because the obvious implementation —
 * handing "07:30" to Intl — turns a 07.30 mass into 00.30 (PRD D-02). So the
 * first test here is the one that would fail if someone "fixed" it.
 */

import {
    DISPLAY_TIMEZONE,
    formatDayName,
    formatWibDate,
    formatWibDateTime,
    formatWibTime,
    toDateTimeLocalValue,
    toIsoWithOffset,
} from '@/lib/datetime';

describe('formatWibTime', () => {
    it('formats a wall clock the way PRD XC-T2 writes it', () => {
        expect(formatWibTime('07:30')).toBe('07.30 WIB');
    });

    it('ignores the seconds MySQL stores', () => {
        // The column is TIME and comes back as H:i:s. Reading only the hour and
        // minute is what keeps a 07.30:00 from rendering as "07. WIB".
        expect(formatWibTime('07:30:00')).toBe('07.30 WIB');
    });

    /**
     * The regression this function exists for. An Intl-based implementation
     * would turn 07.30 into 00.30 here, because a bare wall clock has to be
     * given a date before anything can be formatted — and the offset applied
     * while doing it.
     */
    it('does not shift the hour by the UTC offset', () => {
        expect(formatWibTime('07:30')).not.toBe('00.30 WIB');
        expect(formatWibTime('17:00')).toBe('17.00 WIB');
        expect(formatWibTime('00:15')).toBe('00.15 WIB');
    });

    it('returns the input with the suffix when it is not a clock', () => {
        expect(formatWibTime('bukan jam')).toBe('bukan jam WIB');
    });
});

describe('formatWibDate', () => {
    it('formats an instant in Asia/Pontianak', () => {
        // 02:00 UTC is 09:00 in WIB, on 4 October 2026 — a Sunday.
        expect(formatWibDate('2026-10-04T02:00:00+00:00')).toBe(
            'Minggu, 4 Oktober 2026',
        );
    });

    it('honours an explicit timezone over the default', () => {
        expect(
            formatWibDate('2026-10-04T02:00:00+00:00', 'Asia/Pontianak'),
        ).toBe('Minggu, 4 Oktober 2026');

        // The same instant in UTC is still the 4th, an hour earlier — which is
        // why the timezone is passed rather than read from the device.
        expect(formatWibDate('2026-10-04T02:00:00+00:00', 'UTC')).toContain(
            'Oktober 2026',
        );
    });

    it('returns an empty string rather than printing Invalid Date', () => {
        expect(formatWibDate('bukan tanggal')).toBe('');
    });
});

describe('formatWibDateTime', () => {
    it('joins the date and the clock with the WIB suffix', () => {
        const formatted = formatWibDateTime('2026-10-04T02:00:00+00:00');

        expect(formatted).toContain('Minggu, 4 Oktober 2026');
        expect(formatted).toContain('WIB');
        expect(formatted).toContain('·');
    });

    it('returns an empty string for an unreadable value', () => {
        expect(formatWibDateTime('')).toBe('');
    });
});

describe('formatDayName', () => {
    it('starts the week on Sunday', () => {
        // 0 = Minggu is the same numbering as App\Enums\DayOfWeek and as
        // Carbon's dayOfWeek. The resolver subtracts one from the other, so a
        // change here would move "misa berikutnya" by a whole day.
        expect(formatDayName(0)).toBe('Minggu');
        expect(formatDayName(6)).toBe('Sabtu');
    });

    it('returns an empty string outside 0 to 6', () => {
        expect(formatDayName(7)).toBe('');
        expect(formatDayName(-1)).toBe('');
    });
});

describe('toDateTimeLocalValue', () => {
    it('renders a UTC instant as the local wall clock an input expects', () => {
        // 02:00 UTC is 09:00 WIB. Handing the raw UTC value to a
        // datetime-local input would show the admin a time seven hours off the
        // one they typed.
        expect(
            toDateTimeLocalValue('2026-10-04T02:00:00+00:00', DISPLAY_TIMEZONE),
        ).toBe('2026-10-04T09:00');
    });

    it('returns an empty string for an unreadable value', () => {
        expect(toDateTimeLocalValue('bukan tanggal')).toBe('');
    });
});

describe('toIsoWithOffset', () => {
    it('returns null for an empty field', () => {
        expect(toIsoWithOffset('')).toBeNull();
        expect(toIsoWithOffset('   ')).toBeNull();
    });

    it('returns null for an unreadable value rather than an invalid date', () => {
        expect(toIsoWithOffset('bukan tanggal')).toBeNull();
    });

    it('produces an ISO 8601 string with an offset', () => {
        const iso = toIsoWithOffset('2026-10-04T09:00');

        expect(iso).not.toBeNull();
        // Not an Invalid Date: the value a form posts has to be parseable by the
        // browser and by the server, or the round trip breaks somewhere else.
        expect(Number.isNaN(new Date(iso as string).getTime())).toBe(false);
    });
});
