import { useEffect, useState } from 'react';

import type { CalendarDay } from '@/lib/datetime';

export function useSelectedDate(
    monthDays: CalendarDay[],
    today: string,
    firstEventDate: string | null,
) {
    const [selectedDate, setSelectedDate] = useState<string>(
        () =>
            monthDays.find((day) => day.key === today)?.key ??
            firstEventDate ??
            monthDays[0]?.key ??
            today,
    );

    useEffect(() => {
        const isDateInMonth = monthDays.some((day) => day.key === selectedDate);

        if (!isDateInMonth) {
            setSelectedDate(
                monthDays.find((day) => day.key === today)?.key ??
                    firstEventDate ??
                    monthDays[0]?.key ??
                    today,
            );
        }
    }, [monthDays, today, selectedDate, firstEventDate]);

    return [selectedDate, setSelectedDate] as const;
}
