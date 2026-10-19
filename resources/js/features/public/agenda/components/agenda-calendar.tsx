import { Link } from '@inertiajs/react';
import { Text } from '@/components/text';
import { CALENDAR_DAY_NAMES, wibDateKey, wibMonthGrid } from '@/lib/datetime';
import { show as eventShow } from '@/routes/agenda';
import type { PublicEvent } from '../types';

/**
 * A month, as a grid.
 *
 * A wall of seven equal columns is the wrong shape for a parish whose month has
 * four activities in it. So this is a real grid — it answers "is anything on
 * the 14th" without scrolling — but a day with nothing on it is a quiet square,
 * a day with something is a number, and the list below the grid is what a
 * reader actually reads. Nothing is reachable in the calendar that is not also
 * in the list; the grid is a way of locating it, not a second copy of it.
 */
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
    const grid = wibMonthGrid(monthKey, today);

    /*
     * Grouped once, in WIB.
     *
     * wibDateKey is an Intl.DateTimeFormat call under the hood and a grid asks
     * for it once per event per render; doing it inside the cell loop would call
     * it a hundred times to answer one question.
     *
     * The timezone is not cosmetic here. An activity at 23:00 WIB is 16:00 UTC
     * on the previous day, so slicing the ISO string would file last night's
     * activities in tonight's row. wibDateKey is what makes the square correct.
     */
    const byDate = new Map<string, PublicEvent[]>();

    for (const event of events) {
        const key = wibDateKey(event.start_at, displayTimezone);
        const bucket = byDate.get(key);

        if (bucket) {
            bucket.push(event);
        } else {
            byDate.set(key, [event]);
        }
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full border-collapse">
                <caption className="sr-only">{grid.label}</caption>
                <thead>
                    <tr>
                        {CALENDAR_DAY_NAMES.map((name) => (
                            <th
                                key={name}
                                scope="col"
                                className="pb-2 text-center text-fine-print text-muted-foreground uppercase"
                            >
                                {name}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {grid.weeks.map((week, weekIndex) => (
                        <tr key={`week-${weekIndex}`}>
                            {week.map((day, dayIndex) => {
                                const dayEvents = day.key
                                    ? (byDate.get(day.key) ?? [])
                                    : [];

                                return (
                                    <td
                                        key={
                                            day.key ||
                                            `blank-${weekIndex}-${dayIndex}`
                                        }
                                        className="border border-border p-0 align-top"
                                    >
                                        <div
                                            className={`flex h-16 min-h-16 flex-col gap-1 p-1.5 ${
                                                day.inMonth ? '' : 'bg-muted/40'
                                            }`}
                                        >
                                            <span
                                                className={`text-fine-print tabular-nums ${
                                                    day.isToday
                                                        ? 'font-semibold text-primary-on-surface'
                                                        : 'text-muted-foreground'
                                                }`}
                                            >
                                                {day.inMonth ? day.date : ''}
                                            </span>

                                            {/*
                                             * At most two, because a third would
                                             * overflow a 16-row-high cell. The
                                             * count of what is left links to the
                                             * same list the grid is drawn from,
                                             * so nothing is hidden by the cap.
                                             */}
                                            {dayEvents
                                                .slice(0, 2)
                                                .map((event) => (
                                                    <Link
                                                        key={event.id}
                                                        href={eventShow(
                                                            event.slug,
                                                        )}
                                                        prefetch
                                                        title={event.title}
                                                        className={`block truncate rounded-sm px-1 py-0.5 text-fine-print ${
                                                            event.status ===
                                                            'cancelled'
                                                                ? 'bg-destructive/10 text-destructive line-through'
                                                                : 'bg-secondary text-secondary-foreground hover:bg-primary hover:text-primary-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none'
                                                        }`}
                                                    >
                                                        {event.title}
                                                    </Link>
                                                ))}

                                            {dayEvents.length > 2 ? (
                                                <Text
                                                    as="span"
                                                    variant="fine-print"
                                                    color="muted"
                                                >
                                                    +{dayEvents.length - 2} lagi
                                                </Text>
                                            ) : null}
                                        </div>
                                    </td>
                                );
                            })}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
