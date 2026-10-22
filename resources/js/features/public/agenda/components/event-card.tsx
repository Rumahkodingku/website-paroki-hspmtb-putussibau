import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Clock3,
    MapPin,
} from 'lucide-react';

import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { formatWibClock } from '@/lib/datetime';
import { show as eventShow } from '@/routes/agenda';

export type EventCardData = {
    id: number;
    title: string;
    slug: string;
    summary: string | null;
    start_at: string;
    end_at: string | null;
    is_all_day: boolean;
    location_name: string | null;
    location_url: string | null;
    poster: string | null;
    poster_alt: string | null;
    status: 'scheduled' | 'cancelled' | 'postponed';
    status_label: string;
    category: {
        id: number;
        name: string;
        slug: string;
        color_value: string;
    } | null;
};

export function EventCard({
    event,
    displayTimezone,
    headingLevel = 'h3',
}: {
    event: EventCardData;
    displayTimezone: string;
    headingLevel?: 'h2' | 'h3';
}) {
    const isNotHappening = event.status !== 'scheduled';

    const dateParts = new Intl.DateTimeFormat('id-ID', {
        timeZone: displayTimezone,
        day: '2-digit',
        month: 'short',
    }).formatToParts(new Date(event.start_at));

    const day = dateParts.find((part) => part.type === 'day')?.value;
    const month = dateParts
        .find((part) => part.type === 'month')
        ?.value.replace('.', '')
        .toUpperCase();

    const weekdayYear = new Intl.DateTimeFormat('id-ID', {
        timeZone: displayTimezone,
        weekday: 'long',
        year: 'numeric',
    }).format(new Date(event.start_at));

    const categoryColor = event.category?.color_value;

    return (
        <article
            className={cn(
                'group relative grid min-w-0 grid-cols-1 overflow-hidden',
                'hover:border-primary/30',
                isNotHappening && 'opacity-80',
                'sm:grid-cols-[minmax(0,225px)_minmax(0,1fr)_52px]',
            )}
        >
            {/* Poster and date */}
            <div className="grid grid-cols-[minmax(0,1fr)_84px] gap-2 bg-muted/40 sm:grid-cols-[minmax(0,1fr)_92px]">
                <div className="relative min-h-29 overflow-hidden sm:min-h-33">
                    {event.poster ? (
                        <img
                            src={event.poster}
                            alt={event.poster_alt || `Poster ${event.title}`}
                            loading="lazy"
                            className="absolute inset-0 size-full rounded-lg object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                        />
                    ) : (
                        <div className="absolute inset-0 flex items-center justify-center bg-muted">
                            <CalendarDays
                                aria-hidden="true"
                                className="size-8 text-muted-foreground/50"
                            />
                        </div>
                    )}
                </div>

                <div className="flex flex-col items-center justify-center gap-1 border-r border-border px-2 py-3 text-center">
                    <Text
                        as="span"
                        variant="label"
                        color="brand"
                        className="text-xs font-semibold tracking-wide uppercase"
                    >
                        {month}
                    </Text>

                    <span className="text-3xl leading-none font-semibold tracking-tight text-foreground sm:text-4xl">
                        {day}
                    </span>

                    <Text
                        as="span"
                        variant="fine-print"
                        color="muted"
                        className="text-center"
                    >
                        {weekdayYear}
                    </Text>
                </div>
            </div>

            {/* Event information */}
            <div className="flex min-w-0 flex-col justify-center gap-2.5 p-4 sm:p-5">
                <div className="flex flex-wrap items-center gap-2">
                    {event.category && (
                        <Badge
                            variant="outline"
                            className="gap-1.5 rounded-full border-transparent px-2.5 py-1"
                            style={{
                                color: categoryColor,
                                backgroundColor: categoryColor
                                    ? `${categoryColor}14`
                                    : undefined,
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
                        className="rounded-full px-2.5 py-1"
                    >
                        {event.status === 'scheduled' && (
                            <CheckCircle2
                                aria-hidden="true"
                                className="size-3"
                            />
                        )}
                        {event.status_label}
                    </Badge>
                </div>

                <Text
                    as={headingLevel}
                    variant={headingLevel === 'h2' ? 'h2' : 'h3'}
                    className="line-clamp-2 leading-snug"
                >
                    <Link
                        href={eventShow(event.slug)}
                        prefetch
                        className="rounded-sm outline-none after:absolute after:inset-0 focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        {event.title}
                    </Link>
                </Text>

                {event.summary && (
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="line-clamp-2"
                    >
                        {event.summary}
                    </Text>
                )}

                <div className="flex flex-col gap-2 text-muted-foreground sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-5">
                    <div className="flex min-w-0 items-center gap-1.5">
                        <Clock3
                            aria-hidden="true"
                            className="size-4 shrink-0"
                        />
                        <Text as="span" variant="body-sm">
                            {event.is_all_day
                                ? 'Sepanjang hari'
                                : formatWibClock(
                                      event.start_at,
                                      displayTimezone,
                                  )}
                        </Text>
                    </div>

                    {event.location_name && (
                        <div className="flex min-w-0 items-center gap-1.5">
                            <MapPin
                                aria-hidden="true"
                                className="size-4 shrink-0"
                            />

                            {event.location_url ? (
                                <a
                                    href={event.location_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="relative z-10 truncate rounded-sm text-sm underline-offset-4 hover:text-primary-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    {event.location_name}
                                </a>
                            ) : (
                                <Text
                                    as="span"
                                    variant="body-sm"
                                    className="truncate"
                                >
                                    {event.location_name}
                                </Text>
                            )}
                        </div>
                    )}
                </div>

                {isNotHappening && (
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="font-medium"
                    >
                        {event.status === 'cancelled'
                            ? 'Kegiatan ini dibatalkan.'
                            : 'Jadwal kegiatan ini mengalami perubahan.'}
                    </Text>
                )}
            </div>

            {/* Detail action */}
            <div className="flex items-center justify-end border-t border-border px-4 py-3 sm:justify-center sm:border-t-0 sm:px-1 sm:py-0">
                <Button
                    variant="outline"
                    size="icon"
                    asChild
                    className="relative z-10 size-10"
                >
                    <Link
                        href={eventShow(event.slug)}
                        prefetch
                        aria-label={`Lihat detail ${event.title}`}
                    >
                        <ArrowRight
                            aria-hidden="true"
                            className="size-4 transition-transform group-hover:translate-x-0.5"
                        />
                    </Link>
                </Button>
            </div>
        </article>
    );
}
