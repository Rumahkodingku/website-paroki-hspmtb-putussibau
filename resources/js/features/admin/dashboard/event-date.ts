import type { UpcomingEvent } from './types';

export function getEventDate(
    value: string,
    displayTimezone: string,
): { day: string; month: string; weekday: string } {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return { day: '--', month: '---', weekday: '' };
    }

    const options = { timeZone: displayTimezone };

    return {
        day: new Intl.DateTimeFormat('id-ID', {
            ...options,
            day: 'numeric',
        }).format(date),
        month: new Intl.DateTimeFormat('id-ID', {
            ...options,
            month: 'short',
        })
            .format(date)
            .replace('.', ''),
        weekday: new Intl.DateTimeFormat('id-ID', {
            ...options,
            weekday: 'short',
        })
            .format(date)
            .replace('.', ''),
    };
}

export function getEventTime(
    event: UpcomingEvent,
    displayTimezone: string,
): string {
    if (event.is_all_day) {
        return 'Sepanjang hari';
    }

    return (
        new Intl.DateTimeFormat('id-ID', {
            timeZone: displayTimezone,
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        }).format(new Date(event.start_at)) + ' WIB'
    );
}
