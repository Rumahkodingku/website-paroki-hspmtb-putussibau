/*
 * Formatting for the two kinds of time this application has.
 *
 * There are two, and they are not interchangeable. A recurring mass stores a
 * wall clock — `mass_schedules.start_time` is "07:30" and is not bound to a
 * date (PRD D-02). Everything else is a real instant stored in UTC and rendered
 * in Asia/Pontianak (D-01).
 *
 * formatWibTime deliberately does not touch Intl. Passing "07:30" to a date
 * formatter would require inventing a date, and choosing the wrong one shifts
 * the hour by the UTC offset — a 07.30 mass displayed as 00.30. A wall clock is
 * formatted by replacing its colon and nothing else.
 *
 * Everything else passes an explicit timeZone rather than relying on
 * resolvedOptions().timeZone (roadmap 8: do not depend on the device's clock).
 * DISPLAY_TIMEZONE is the default, and the shared prop `displayTimezone`
 * overrides it, so the page follows app.display_timezone rather than a second
 * copy of Asia/Pontianak living here.
 */

/** Asia/Pontianak (WIB). The app's display timezone per D-01. */
export const DISPLAY_TIMEZONE = 'Asia/Pontianak';

/** 0 = Minggu, matching App\Enums\DayOfWeek and Carbon's dayOfWeek. */
const DAY_NAMES = [
    'Minggu',
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu',
] as const;

function timezone(value?: string | null): string {
    return value && value.trim() !== '' ? value : DISPLAY_TIMEZONE;
}

/**
 * "07:30" to "07.30 WIB" (XC-T2).
 *
 * A string operation, not a date one. `start_time` arrives as "07:30" with no
 * date, and constructing a Date from it would put it on 1 January 1970 and then
 * apply the UTC offset to the hour.
 */
export function formatWibTime(time: string): string {
    const [hours = '', minutes = ''] = time.split(':');

    if (hours === '' || minutes === '') {
        return `${time} WIB`;
    }

    return `${hours}.${minutes} WIB`;
}

/**
 * "2026-10-04T00:00:00+00:00" to "Minggu, 5 Oktober 2026" (XC-T2).
 *
 * An invalid date is returned as an empty string rather than "Invalid Date": a
 * mistyped timestamp should leave a gap on the page, not print the word Date in
 * three languages.
 */
export function formatWibDate(iso: string, timeZone?: string | null): string {
    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return new Intl.DateTimeFormat('id-ID', {
        timeZone: timezone(timeZone),
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

/**
 * "Minggu, 5 Oktober 2026 · 07.30 WIB".
 *
 * The clock is assembled from formatToParts rather than read out of a formatted
 * string, because the separator between hours and minutes is the locale's
 * choice: id-ID writes "09.30" with a dot, en-GB writes "09:30" with a colon,
 * and reading the formatted string would therefore produce two different
 * formats for the same clock depending on which locale the browser picked.
 * XC-T2 writes "07.30 WIB", so the dot is explicit here.
 *
 * WIB is spelled out rather than left to the reader, and the middle dot is the
 * separator the Indonesian format in XC-T2 uses between date and time.
 */
export function formatWibDateTime(
    iso: string,
    timeZone?: string | null,
): string {
    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: timezone(timeZone),
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).formatToParts(date);

    const hour = parts.find((part) => part.type === 'hour')?.value ?? '00';
    const minute = parts.find((part) => part.type === 'minute')?.value ?? '00';

    return `${formatWibDate(iso, timeZone)} · ${hour}.${minute} WIB`;
}

/**
 * 0 to "Minggu".
 *
 * Out-of-range values return an empty string rather than "undefined": the day
 * comes from an enum on the server, so a value outside 0..6 means the contract
 * broke, and a blank is a visible symptom while "undefined" is a silently
 * wrong label.
 */
export function formatDayName(day: number): string {
    return DAY_NAMES[day] ?? '';
}

/**
 * A datetime-local input's value as ISO 8601 with an offset, or null when the
 * field is empty.
 *
 * The admin forms post a local wall clock with no offset. Sending that as-is
 * loses the timezone and leaves the server guessing; the conversion belongs in
 * one place so it cannot be done twice or half of the time.
 */
export function toIsoWithOffset(value: string): string | null {
    if (value.trim() === '') {
        return null;
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? null : parsed.toISOString();
}

/**
 * An ISO 8601 instant as the value a datetime-local input expects.
 *
 * Used to fill an edit form: the server sends UTC, the input wants a local
 * wall clock, and converting without the explicit zone would use the device's.
 */
export function toDateTimeLocalValue(
    iso: string,
    timeZone?: string | null,
): string {
    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: timezone(timeZone),
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).formatToParts(date);

    const get = (type: Intl.DateTimeFormatPartTypes): string =>
        parts.find((part) => part.type === type)?.value ?? '';

    // en-CA already yields YYYY-MM-DD; the clock is assembled here so the
    // result matches what <input type="datetime-local"> accepts.
    return `${get('year')}-${get('month')}-${get('day')}T${get('hour')}:${get('minute')}`;
}

/*
|--------------------------------------------------------------------------
| Calendar days and months
|--------------------------------------------------------------------------
|
| Everything below works on `YYYY-MM-DD` strings rather than on Date objects,
| and that is the whole design. `new Date('2026-10-05')` is parsed as UTC
| midnight, so calling getDate() on it anywhere east of Greenwich returns the
| 4th — which is how a calendar ends up permanently one day out and nobody can
| say why. Working on the string sidesteps the timezone entirely.
|
| A day on a calendar is a label, not an instant. There is no UTC moment called
| "the 5th of October", so the only question worth asking is which label a
| given instant belongs to, and that is answered once, here, with the display
| timezone made explicit.
|
| Asia/Pontianak is UTC+7 year-round and observes no DST, which is what makes
| the month arithmetic below safe without a date library. See
| docs/plan-implementation/plan-phase-05-news-agenda.md section 7.2.
*/

/** One cell of a month grid. */
export type CalendarDay = {
    /** `YYYY-MM-DD`. The identity of the day, in the display timezone. */
    key: string;
    /** 1-31. */
    date: number;
    /** Whether the day belongs to the month being shown. */
    inMonth: boolean;
    /** Whether the day is today in the display timezone. */
    isToday: boolean;
};

/** A month grid: `leadingBlanks` empty cells, then the weeks. */
export type CalendarMonth = {
    /** `YYYY-MM`, as it appears in the URL. */
    key: string;
    /** "Oktober 2026" */
    label: string;
    /** 0 = Minggu, matching the grid's column order. */
    firstDayOfWeek: number;
    /** Cells to skip before the 1st, so the 1st lands in the right column. */
    leadingBlanks: number;
    /** Always whole weeks, so the grid is rectangular rather than ragged. */
    weeks: CalendarDay[][];
};

/**
 * A UTC instant as `YYYY-MM-DD` in the parish's timezone.
 *
 * The function the whole calendar rests on. An activity at 23.30 WIB on the
 * 30th is the 30th in Pontianak and may be the 29th elsewhere; only one of those
 * answers "which square of the grid does this square".
 */
export function wibDateKey(iso: string, timeZone?: string | null): string {
    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    // en-CA formats as YYYY-MM-DD, which is already the key shape.
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: timezone(timeZone),
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(date);
}

/** Today as `YYYY-MM-DD` in the parish's timezone. */
export function wibTodayKey(timeZone?: string | null): string {
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: timezone(timeZone),
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date());
}

/** `YYYY-MM` as "Oktober 2026". */
export function wibMonthLabel(
    monthKey: string,
    timeZone?: string | null,
): string {
    const [year = '', month = ''] = monthKey.split('-');

    if (year === '' || month === '') {
        return monthKey;
    }

    // Day 1 midday: a month built at midnight can land in the previous month
    // when formatted at midnight in a zone east of the one the parts were read
    // in, and a calendar that opens on September is worse than one that opens on
    // an empty label.
    const date = new Date(Number(year), Number(month) - 1, 1, 12);

    if (Number.isNaN(date.getTime())) {
        return monthKey;
    }

    return new Intl.DateTimeFormat('id-ID', {
        timeZone: timezone(timeZone),
        month: 'long',
        year: 'numeric',
    }).format(date);
}

/** The month `YYYY-MM` before or after the given one. */
export function wibShiftMonth(monthKey: string, by: number): string {
    const [year = '', month = ''] = monthKey.split('-');
    const start = new Date(Number(year), Number(month) - 1 + by, 1, 12);

    if (Number.isNaN(start.getTime())) {
        return monthKey;
    }

    const nextYear = String(start.getFullYear());
    const nextMonth = String(start.getMonth() + 1).padStart(2, '0');

    return `${nextYear}-${nextMonth}`;
}

/**
 * A rectangular month grid.
 *
 * `todayKey` is passed in rather than read from the clock, so "today" is the
 * one the server computed in the display timezone and the highlight cannot
 * disagree with the "Hari Ini" button that navigates to it.
 */
export function wibMonthGrid(
    monthKey: string,
    todayKey: string,
): CalendarMonth {
    const [year = '', month = ''] = monthKey.split('-');

    if (year === '' || month === '') {
        return {
            key: monthKey,
            label: monthKey,
            firstDayOfWeek: 0,
            leadingBlanks: 0,
            weeks: [],
        };
    }

    const first = new Date(Number(year), Number(month) - 1, 1, 12);
    const daysInMonth = new Date(Number(year), Number(month), 0, 12).getDate();
    const leadingBlanks = first.getDay();

    const cells: CalendarDay[] = [];

    // Leading blanks are real cells rather than skipped ones, so every week in
    // the grid has seven squares and the column headers line up with something.
    for (let index = 0; index < leadingBlanks; index++) {
        cells.push({ key: '', date: 0, inMonth: false, isToday: false });
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dayKey = `${year}-${month}-${String(day).padStart(2, '0')}`;

        cells.push({
            key: dayKey,
            date: day,
            inMonth: true,
            isToday: dayKey === todayKey,
        });
    }

    // Trailing blanks up to a whole week, so the grid is a rectangle. A ragged
    // last row makes the month look like it ended early.
    while (cells.length % 7 !== 0) {
        cells.push({ key: '', date: 0, inMonth: false, isToday: false });
    }

    const weeks: CalendarDay[][] = [];

    for (let index = 0; index < cells.length; index += 7) {
        weeks.push(cells.slice(index, index + 7));
    }

    return {
        key: monthKey,
        label: wibMonthLabel(monthKey),
        firstDayOfWeek: 0,
        leadingBlanks,
        weeks,
    };
}

/** The seven day names a calendar grid's columns are headed with. */
export const CALENDAR_DAY_NAMES = [
    'Min',
    'Sen',
    'Sel',
    'Rab',
    'Kam',
    'Jum',
    'Sab',
] as const;

/**
 * A Google Calendar "add this event" URL.
 *
 * Built with URLSearchParams rather than by string concatenation, because the
 * encoding of a title with an ampersand in it is exactly the case AC-EVT-3 is
 * about and hand-concatenation gets it wrong silently.
 *
 * An all-day activity has no clock, so it is sent as a single date and Google
 * shows it as an all-day entry rather than as one starting at midnight.
 */
export function googleCalendarUrl(options: {
    title: string;
    /** ISO 8601 instants, as the server sent them. */
    start: string;
    end?: string | null;
    details?: string | null;
    location?: string | null;
    isAllDay?: boolean;
    timeZone?: string | null;
}): string {
    const params = new URLSearchParams({ action: 'TEMPLATE' });

    params.set('text', options.title);

    if (options.isAllDay) {
        // Google's all-day form wants a bare YYYYMMDD, not an instant.
        params.set(
            'dates',
            `${compactDate(options.start)}/${compactDate(options.end ?? options.start)}`,
        );
    } else {
        const start = compactInstant(options.start);

        params.set(
            'dates',
            options.end ? `${start}/${compactInstant(options.end)}` : start,
        );
    }

    if (options.details) {
        params.set('details', options.details);
    }

    if (options.location) {
        params.set('location', options.location);
    }

    // Without this Google guesses the viewer's own timezone, and a parish
    // calendar read by somebody in Europe moves every event by six hours.
    params.set('ctz', timezone(options.timeZone));

    // URLSearchParams encodes a space as `+`, which is correct for a form body
    // and widely accepted in a query string — but not universal, and a title
    // that reads "Retreat+Rekoleksi" in a calendar client is a bug somebody
    // files. `%20` is unambiguous everywhere.
    return `https://calendar.google.com/calendar/render?${params
        .toString()
        .replace(/\+/g, '%20')}`;
}

/** An ISO instant as Google's `YYYYMMDDTHHMMSSZ`. */
function compactInstant(iso: string): string {
    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date
        .toISOString()
        .replace(/[-:]/g, '')
        .replace(/\.\d{3}/, '');
}

/** An ISO instant as the `YYYYMMDD` an all-day entry needs. */
function compactDate(iso: string): string {
    return wibDateKey(iso).replace(/-/g, '');
}
