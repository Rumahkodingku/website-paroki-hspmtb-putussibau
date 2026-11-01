import { formatWibClock, formatWibDayParts } from '@/lib/datetime';
import type { UpcomingEvent } from './types';

export function getEventDate(
    value: string,
    displayTimezone: string,
): { day: string; month: string; weekday: string } {
    if (Number.isNaN(new Date(value).getTime())) {
        return { day: '--', month: '---', weekday: '' };
    }

    const parts = formatWibDayParts(value, displayTimezone);

    return {
        day: parts.day,
        month: parts.month,
        weekday: parts.weekday,
    };
}

export function getEventTime(
    event: UpcomingEvent,
    displayTimezone: string,
): string {
    if (event.is_all_day) {
        return 'Sepanjang hari';
    }

    return formatWibClock(event.start_at, displayTimezone);
}
