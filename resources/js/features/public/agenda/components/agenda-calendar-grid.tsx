import { CALENDAR_DAY_NAMES, type CalendarMonth } from '@/lib/datetime';
import type { PublicEvent } from '../types';

export function AgendaCalendarGrid({
    grid,
    byDate,
    selectedDate,
    today,
    onSelectDate,
}: {
    grid: CalendarMonth;
    byDate: Map<string, PublicEvent[]>;
    selectedDate: string;
    today: string;
    onSelectDate: (key: string) => void;
}) {
    return (
        <div className="min-w-0">
            <table className="w-full table-fixed">
                <caption className="sr-only">
                    Kalender {grid.label}. Pilih tanggal untuk melihat kegiatan
                    paroki.
                </caption>

                <thead>
                    <tr>
                        {CALENDAR_DAY_NAMES.map((name) => (
                            <th
                                key={name}
                                scope="col"
                                className="pb-2 text-center text-[10px] font-medium text-muted-foreground sm:pb-3 sm:text-xs"
                            >
                                <span className="sm:hidden">
                                    {name.slice(0, 1)}
                                </span>
                                <span className="hidden sm:inline">{name}</span>
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

                                const isSelected = day.key === selectedDate;

                                const isCurrentDate = day.key === today;

                                const categoryColors = [
                                    ...new Set(
                                        dayEvents
                                            .map(
                                                (event) =>
                                                    event.category?.color_value,
                                            )
                                            .filter((color): color is string =>
                                                Boolean(color),
                                            ),
                                    ),
                                ].slice(0, 3);

                                return (
                                    <td
                                        key={
                                            day.key ??
                                            `blank-${weekIndex}-${dayIndex}`
                                        }
                                        className="border border-border p-0 align-top"
                                    >
                                        <button
                                            type="button"
                                            disabled={!day.inMonth}
                                            onClick={() => {
                                                if (day.key) {
                                                    onSelectDate(day.key);
                                                }
                                            }}
                                            aria-pressed={
                                                day.inMonth
                                                    ? isSelected
                                                    : undefined
                                            }
                                            aria-label={
                                                day.key
                                                    ? `${day.date}, ${dayEvents.length} kegiatan`
                                                    : 'Tanggal kosong'
                                            }
                                            className={[
                                                'flex min-h-12 w-full flex-col items-center justify-start gap-1 px-0.5 py-1.5',
                                                'transition-colors focus-visible:relative focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:min-h-16.5 sm:items-start sm:px-2 sm:py-2',
                                                !day.inMonth
                                                    ? 'cursor-default bg-muted/20 text-muted-foreground/40'
                                                    : isSelected
                                                      ? 'bg-primary/5'
                                                      : 'hover:bg-muted/60',
                                            ].join(' ')}
                                        >
                                            <span
                                                className={[
                                                    'flex size-6 items-center justify-center rounded-full text-xs tabular-nums sm:size-7 sm:text-sm',
                                                    isSelected
                                                        ? 'bg-primary font-semibold text-primary-foreground'
                                                        : isCurrentDate
                                                          ? 'border border-primary font-semibold text-primary'
                                                          : day.inMonth
                                                            ? 'text-foreground'
                                                            : 'text-muted-foreground/40',
                                                ].join(' ')}
                                            >
                                                {day.date}
                                            </span>

                                            {/* Category indicators */}
                                            <span
                                                aria-hidden="true"
                                                className="flex min-h-1.5 items-center justify-center gap-0.5 sm:ml-1 sm:justify-start"
                                            >
                                                {categoryColors.map((color) => (
                                                    <span
                                                        key={color}
                                                        className="size-1 rounded-full sm:size-1.5"
                                                        style={{
                                                            backgroundColor:
                                                                color,
                                                        }}
                                                    />
                                                ))}

                                                {dayEvents.length > 3 && (
                                                    <span className="hidden text-[9px] text-muted-foreground sm:inline">
                                                        +{dayEvents.length - 3}
                                                    </span>
                                                )}
                                            </span>

                                            {/* Event titles are hidden on mobile
                                                to preserve equal calendar cells. */}
                                            <span className="hidden w-full sm:block">
                                                {dayEvents
                                                    .slice(0, 1)
                                                    .map((event) => (
                                                        <span
                                                            key={event.id}
                                                            className={[
                                                                'block truncate rounded px-1 py-0.5 text-left text-[10px]',
                                                                event.status ===
                                                                'cancelled'
                                                                    ? 'bg-destructive/10 text-destructive line-through'
                                                                    : 'bg-secondary text-secondary-foreground',
                                                            ].join(' ')}
                                                        >
                                                            {event.title}
                                                        </span>
                                                    ))}
                                            </span>
                                        </button>
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
