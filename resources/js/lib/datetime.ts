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
