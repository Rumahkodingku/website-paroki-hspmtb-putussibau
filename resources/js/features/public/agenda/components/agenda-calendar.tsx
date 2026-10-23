import { useMemo } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Text } from '@/components/text';
import { wibDateKey, wibMonthGrid } from '@/lib/datetime';
import type { PublicEvent } from '../types';
import { AgendaCalendarGrid } from './agenda-calendar-grid';
import { AgendaSelectedDayPanel } from './agenda-selected-day-panel';
import { useSelectedDate } from '../hooks/use-selected-date';

export function AgendaCalendar({
    monthKey,
    today,
    events,
    displayTimezone,
}: {
    monthKey: string;
    today: string;
    events: PublicEvent[];
    displayTimezone: string;
}) {
    const grid = useMemo(
        () => wibMonthGrid(monthKey, today),
        [monthKey, today],
    );

    const byDate = useMemo(() => {
        const result = new Map<string, PublicEvent[]>();

        for (const event of events) {
            const key = wibDateKey(event.start_at, displayTimezone);

            const bucket = result.get(key);

            if (bucket) {
                bucket.push(event);
            } else {
                result.set(key, [event]);
            }
        }

        return result;
    }, [events, displayTimezone]);

    const monthDays = useMemo(
        () => grid.weeks.flat().filter((day) => day.inMonth && day.key),
        [grid],
    );

    const firstEventDate = useMemo(
        () => monthDays.find((day) => byDate.has(day.key))?.key ?? null,
        [monthDays, byDate],
    );

    const [selectedDate, setSelectedDate] = useSelectedDate(
        monthDays,
        today,
        firstEventDate,
    );

    const selectedEvents = byDate.get(selectedDate) ?? [];

    return (
        <section
            aria-label="Kalender agenda paroki"
            className="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.7fr)_minmax(300px,0.95fr)] lg:gap-5"
        >
            {/* Monthly calendar */}
            <div className="min-w-0 rounded-xl p-3 sm:p-4 lg:p-5">
                {/* Month navigation */}
                <div className="mb-4 flex items-center justify-between gap-2 sm:mb-5">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-9 shrink-0 rounded-full"
                        aria-label="Tanggal sebelumnya"
                        onClick={() => {
                            const index = monthDays.findIndex(
                                (day) => day.key === selectedDate,
                            );

                            if (index > 0) {
                                setSelectedDate(monthDays[index - 1].key);
                            }
                        }}
                    >
                        <ChevronLeft className="size-4" />
                    </Button>

                    <div className="mb-4 min-w-0 text-center">
                        <Text as="h3" variant="h3" className="truncate">
                            {grid.label}
                        </Text>

                        <Text
                            as="p"
                            variant="fine-print"
                            color="muted"
                            className="mt-1 hidden sm:block"
                        >
                            Pilih tanggal untuk melihat agenda
                        </Text>
                    </div>

                    <div className="flex shrink-0 items-center gap-1.5">
                        {selectedDate !== today && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    const todayInMonth = monthDays.some(
                                        (day) => day.key === today,
                                    );

                                    if (todayInMonth) {
                                        setSelectedDate(today);
                                    }
                                }}
                                disabled={
                                    !monthDays.some((day) => day.key === today)
                                }
                                className="hidden sm:inline-flex"
                            >
                                Hari ini
                            </Button>
                        )}

                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-9 shrink-0 rounded-full"
                            aria-label="Tanggal berikutnya"
                            onClick={() => {
                                const index = monthDays.findIndex(
                                    (day) => day.key === selectedDate,
                                );

                                if (
                                    index >= 0 &&
                                    index < monthDays.length - 1
                                ) {
                                    setSelectedDate(monthDays[index + 1].key);
                                }
                            }}
                        >
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                </div>

                {selectedDate !== today &&
                    monthDays.some((day) => day.key === today) && (
                        <div className="mb-3 flex justify-end sm:hidden">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setSelectedDate(today)}
                            >
                                Kembali ke hari ini
                            </Button>
                        </div>
                    )}

                {/* Calendar grid */}
                <AgendaCalendarGrid
                    grid={grid}
                    byDate={byDate}
                    selectedDate={selectedDate}
                    today={today}
                    onSelectDate={setSelectedDate}
                />
            </div>

            {/* Selected-day agenda panel */}
            <AgendaSelectedDayPanel
                selectedDate={selectedDate}
                selectedEvents={selectedEvents}
                displayTimezone={displayTimezone}
                events={events}
            />
        </section>
    );
}
