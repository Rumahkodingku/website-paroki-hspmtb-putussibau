import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarClock, ChevronRight, MapPin } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Text } from '@/components/text';
import { create as createEvent, list as eventsList } from '@/routes/events';
import { cn } from '@/lib/utils';
import { contentEditHref } from '../content-target';
import { getEventDate, getEventTime } from '../event-date';
import type { UpcomingEvent } from '../types';
import { ContentStatusBadge } from './content-status-badge';
import { Panel } from './panel';

function UpcomingEventItem({
    event,
    displayTimezone,
}: {
    event: UpcomingEvent;
    displayTimezone: string;
}) {
    const date = getEventDate(event.start_at, displayTimezone);

    return (
        <li>
            <Link
                href={contentEditHref('event', event.id)}
                className={cn(
                    'group flex min-w-0 items-center gap-3 px-4 py-4',
                    'transition-colors hover:bg-muted/50',
                    'focus-visible:relative focus-visible:z-10',
                    'focus-visible:ring-2 focus-visible:outline-none',
                    'focus-visible:ring-ring focus-visible:ring-inset',
                    'sm:gap-4 sm:px-5',
                )}
            >
                {/* Calendar date */}
                <div
                    className={cn(
                        'flex size-12 shrink-0 flex-col items-center justify-center',
                        'rounded-xl border border-border bg-muted/40',
                        'transition-colors group-hover:border-primary/20',
                        'group-hover:bg-primary/5 sm:size-14',
                    )}
                    aria-label={`${date.weekday}, ${date.day} ${date.month}`}
                >
                    <Text
                        as="span"
                        className="text-lg leading-5 font-bold text-foreground tabular-nums sm:text-xl"
                    >
                        {date.day}
                    </Text>

                    <Text
                        as="span"
                        variant="fine-print"
                        className="mt-0.5 font-medium text-muted-foreground capitalize"
                    >
                        {date.month}
                    </Text>
                </div>

                {/* Event details */}
                <div className="min-w-0 flex-1 space-y-1.5">
                    <div className="flex min-w-0 flex-wrap items-center gap-2">
                        <Text
                            as="span"
                            variant="label"
                            className="min-w-0 flex-1 leading-snug font-semibold text-foreground"
                            lineClamp={2}
                        >
                            {event.title}
                        </Text>

                        <ContentStatusBadge
                            status={event.status}
                            label={event.status_label}
                            isPublished={event.is_published}
                        />
                    </div>

                    <Text
                        as="p"
                        variant="fine-print"
                        color="muted"
                        className="leading-5"
                    >
                        {date.weekday}, {date.day} {date.month}
                        {' · '}
                        {getEventTime(event, displayTimezone)}
                    </Text>

                    {event.location_name ? (
                        <div className="flex min-w-0 items-start gap-1.5">
                            <MapPin
                                className="mt-0.5 size-3.5 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />

                            <Text
                                as="span"
                                variant="fine-print"
                                color="muted"
                                className="min-w-0 leading-5"
                                lineClamp={2}
                            >
                                {event.location_name}
                            </Text>
                        </div>
                    ) : null}
                </div>

                {/* Navigation indicator */}
                <ChevronRight
                    className={cn(
                        'size-4 shrink-0 text-muted-foreground/50',
                        'transition-all group-hover:translate-x-0.5',
                        'group-hover:text-primary',
                    )}
                    aria-hidden="true"
                />
            </Link>
        </li>
    );
}

export function UpcomingEventsPanel({
    events,
    displayTimezone,
    className,
}: {
    events: UpcomingEvent[];
    displayTimezone: string;
    className?: string;
}) {
    return (
        <Panel
            title="Agenda Terdekat"
            className={cn('h-full', className)}
            action={
                <Link
                    href={eventsList().url}
                    className={cn(
                        'inline-flex min-h-9 items-center gap-1.5 rounded-md',
                        'text-sm font-medium text-primary',
                        'transition-colors hover:text-primary/80',
                        'focus-visible:ring-2 focus-visible:outline-none',
                        'focus-visible:ring-ring focus-visible:ring-offset-2',
                    )}
                >
                    <span>Lihat semua</span>
                    <ArrowRight className="size-4" aria-hidden="true" />
                </Link>
            }
        >
            {events.length === 0 ? (
                <EmptyState
                    icon={CalendarClock}
                    title="Belum ada agenda mendatang"
                    description="Agenda yang dipublikasikan dan belum berlangsung akan muncul di sini."
                    action={
                        <Link
                            href={createEvent().url}
                            className="text-sm font-medium text-primary underline-offset-4 hover:underline"
                        >
                            Tambah Agenda
                        </Link>
                    }
                />
            ) : (
                <ul className="divide-y divide-border">
                    {events.slice(0, 3).map((event) => (
                        <UpcomingEventItem
                            key={event.id}
                            event={event}
                            displayTimezone={displayTimezone}
                        />
                    ))}
                </ul>
            )}
        </Panel>
    );
}
