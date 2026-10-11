import { describe, expect, it } from 'vite-plus/test';
import {
    wibDateKey,
    wibMonthGrid,
    wibMonthLabel,
    wibShiftMonth,
    googleCalendarUrl,
} from '@/lib/datetime';

describe('calendar helpers', () => {
    it('maps an instant to the parish day, not the device day', () => {
        // 23:30 WIB on the 30th is 16:30 UTC the same day; a device in UTC-5 would
        // call this the 30th too, but one east of Greenwich would say the 31st.
        expect(wibDateKey('2026-10-30T16:30:00+00:00')).toBe('2026-10-30');
        // 00:30 WIB on the 31st is 17:30 UTC on the 30th — the classic off-by-one.
        expect(wibDateKey('2026-10-30T17:30:00+00:00')).toBe('2026-10-31');
    });

    it('builds a rectangular grid for a month starting on a Friday', () => {
        const g = wibMonthGrid('2026-10', '2026-10-15');
        expect(g.label).toBe('Oktober 2026');
        expect(g.leadingBlanks).toBe(4); // 1 Oct 2026 is a Thursday -> 4 blanks
        expect(g.weeks.every((w) => w.length === 7)).toBe(true);
        expect(g.weeks.flat().filter((d) => d.inMonth)).toHaveLength(31);
        expect(g.weeks.flat().find((d) => d.isToday)?.date).toBe(15);
    });

    it('shifts months across a year boundary', () => {
        expect(wibShiftMonth('2026-01', -1)).toBe('2025-12');
        expect(wibShiftMonth('2026-12', 1)).toBe('2027-01');
    });

    it('labels a month in Indonesian', () => {
        expect(wibMonthLabel('2026-01')).toContain('2026');
    });

    it('builds a google calendar url that encodes an ampersand', () => {
        const url = googleCalendarUrl({
            title: 'Retreat & Rekoleksi',
            start: '2026-12-01T02:00:00+00:00',
            end: '2026-12-01T10:00:00+00:00',
            details: 'a & b',
            location: 'Gereja Paroki',
        });
        expect(url).toContain('action=TEMPLATE');
        // %20 and %26, never a bare `&` or `+`. AC-EVT-3 is about this.
        expect(url).toContain('Retreat%20%26%20Rekoleksi');
        expect(url).toContain('20261201T020000Z%2F20261201T100000Z');
        expect(url).toContain('ctz=Asia%2FPontianak');
    });

    it('sends an all-day entry as bare dates', () => {
        const url = googleCalendarUrl({
            title: 'Retreat',
            start: '2026-12-01T02:00:00+00:00',
            end: '2026-12-02T02:00:00+00:00',
            isAllDay: true,
        });
        expect(url).toContain('dates=20261201%2F20261202');
    });
});
