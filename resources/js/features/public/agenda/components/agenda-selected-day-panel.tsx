import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, Clock3, MapPin } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Text } from '@/components/text';
import { show as eventShow } from '@/routes/agenda';
import type { PublicEvent } from '../types';

export function AgendaSelectedDayPanel({
    selectedDate,
    selectedEvents,
    displayTimezone,
    events,
}: {
    selectedDate: string;
    selectedEvents: PublicEvent[];
    displayTimezone: string;
    events: PublicEvent[];
}) {
    const selectedDateLabel = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: displayTimezone,
    }).format(new Date(`${selectedDate}T12:00:00Z`));

    const formatEventTime = (value: string) =>
        new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: displayTimezone,
        })
            .format(new Date(value))
            .replace('.', ':') + ' WIB';

    const formatEventRange = (event: PublicEvent) => {
        if (event.is_all_day) {
            return 'Sepanjang hari';
        }

        const start = formatEventTime(event.start_at);

        if (!event.end_at || event.end_at === event.start_at) {
            return start;
        }

        return `${start} – ${formatEventTime(event.end_at)}`;
    };

    return (
        <aside
            aria-labelledby="agenda-selected-title"
            aria-live="polite"
            className="b flex min-w-0 flex-col border-l p-4 sm:p-5"
        >
            <div className="mb-4 flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <Text
                        variant="fine-print"
                        className="text-[10px] font-semibold tracking-wider uppercase"
                    >
                        {selectedDateLabel}
                    </Text>

                    <Text
                        as="h4"
                        variant="h4"
                        id="agenda-selected-title"
                        className="mt-1"
                    >
                        Agenda pada tanggal ini
                    </Text>
                </div>

                <Badge variant="outline" className="shrink-0 rounded-full">
                    {selectedEvents.length} kegiatan
                </Badge>
            </div>

            {selectedEvents.length > 0 ? (
                <div className="divide-y divide-border">
                    {selectedEvents.map((event) => (
                        <article
                            key={event.id}
                            className="flex gap-3 py-4 first:pt-0 last:pb-0"
                        >
                            {event.poster ? (
                                <img
                                    src={event.poster}
                                    alt={
                                        event.poster_alt ||
                                        `Poster ${event.title}`
                                    }
                                    loading="lazy"
                                    className="size-18 shrink-0 rounded-lg object-cover sm:size-20"
                                />
                            ) : (
                                <div className="flex size-18 shrink-0 items-center justify-center rounded-lg bg-muted sm:size-20">
                                    <CalendarDays
                                        aria-hidden="true"
                                        className="size-7 text-muted-foreground"
                                    />
                                </div>
                            )}

                            <div className="min-w-0 flex-1">
                                <div className="mb-2 flex flex-wrap items-center gap-1.5">
                                    {event.category && (
                                        <Badge
                                            variant="outline"
                                            className="gap-1 rounded-full border-transparent px-2 py-0.5 text-[10px] sm:text-xs"
                                            style={{
                                                color: event.category
                                                    .color_value,
                                                backgroundColor: `${event.category.color_value}14`,
                                            }}
                                        >
                                            {event.category.name}
                                        </Badge>
                                    )}

                                    <Badge
                                        variant={
                                            event.status === 'cancelled'
                                                ? 'destructive'
                                                : 'secondary'
                                        }
                                        className="rounded-full px-2 py-0.5 text-[10px] sm:text-xs"
                                    >
                                        {event.status_label}
                                    </Badge>
                                </div>

                                <Text
                                    as="h4"
                                    variant="label"
                                    className="text-sm leading-snug font-semibold sm:text-base"
                                >
                                    <Link
                                        href={eventShow(event.slug)}
                                        prefetch
                                        className="rounded-sm hover:text-primary-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    >
                                        {event.title}
                                    </Link>
                                </Text>

                                <div className="mt-2 space-y-1.5 text-muted-foreground">
                                    <div className="flex items-start gap-1.5">
                                        <Clock3 className="mt-0.5 size-3.5 shrink-0" />
                                        <Text as="span" variant="fine-print">
                                            {formatEventRange(event)}
                                        </Text>
                                    </div>

                                    {event.location_name && (
                                        <div className="flex items-start gap-1.5">
                                            <MapPin className="mt-0.5 size-3.5 shrink-0" />

                                            <Text
                                                as="span"
                                                variant="fine-print"
                                                className="wrap-break-word"
                                            >
                                                {event.location_name}
                                            </Text>
                                        </div>
                                    )}
                                </div>

                                {event.summary && (
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                        className="mt-2 line-clamp-2"
                                    >
                                        {event.summary}
                                    </Text>
                                )}

                                <Link
                                    href={eventShow(event.slug)}
                                    prefetch
                                    className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:text-sm"
                                >
                                    Lihat detail kegiatan
                                    <ArrowRight className="size-3.5" />
                                </Link>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <div className="flex flex-1 flex-col items-center justify-center py-8 text-center">
                    <div className="mb-3 flex size-12 items-center justify-center rounded-full bg-muted">
                        <CalendarDays
                            aria-hidden="true"
                            className="size-6 text-muted-foreground"
                        />
                    </div>

                    <Text as="p" variant="label">
                        Belum ada agenda
                    </Text>

                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="mt-1 max-w-xs"
                    >
                        Tidak ada kegiatan yang dijadwalkan pada tanggal ini.
                        Silakan pilih tanggal lain.
                    </Text>
                </div>
            )}

            <div className="mt-auto pt-5">
                <Link
                    href={events.length > 0 ? eventShow(events[0].slug) : '#'}
                    className="flex items-center justify-between gap-3 rounded-lg border border-primary/10 bg-primary/4 p-3 text-sm transition-colors hover:bg-primary/8 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <span className="flex min-w-0 items-center gap-2">
                        <CalendarDays className="size-5 shrink-0 text-primary-on-surface" />
                        <span className="min-w-0">
                            <span className="block font-semibold">
                                Agenda bulan ini
                            </span>
                            <span className="block text-xs text-muted-foreground">
                                {events.length} kegiatan pada bulan ini
                            </span>
                        </span>
                    </span>

                    <ArrowRight className="size-4 shrink-0 text-primary-on-surface" />
                </Link>
            </div>
        </aside>
    );
}
