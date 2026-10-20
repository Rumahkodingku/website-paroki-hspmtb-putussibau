import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarClock, MapPin } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Text } from '@/components/text';
import { formatWibDate, formatWibDateTime } from '@/lib/datetime';
import { create as createEvent, list as eventsList } from '@/routes/events';
import { cn } from '@/lib/utils';
import { contentEditHref } from '../content-target';
import type { UpcomingEvent } from '../types';
import { ContentStatusBadge } from './content-status-badge';
import { Panel } from './panel';

/*
 * Agenda Terdekat: the next few published activities, earliest first.
 *
 * Cancelled and postponed activities are labelled, never hidden or shown as
 * ordinary — an admin glancing at this panel must see that Thursday's meeting
 * was called off. The list is already bounded and time-filtered by the
 * controller, so this component only renders.
 */

function eventWhen(event: UpcomingEvent, displayTimezone: string): string {
    return event.is_all_day
        ? formatWibDate(event.start_at, displayTimezone)
        : formatWibDateTime(event.start_at, displayTimezone);
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
                    className="inline-flex items-center gap-1 text-label text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    Lihat semua
                    <ArrowRight className="size-3.5" aria-hidden="true" />
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
                            className="text-label text-primary-on-surface underline-offset-4 hover:underline"
                        >
                            Tambah Agenda
                        </Link>
                    }
                />
            ) : (
                <ul className="divide-y divide-border">
                    {events.map((event) => (
                        <li key={event.id}>
                            <Link
                                href={contentEditHref('event', event.id)}
                                className="flex flex-col gap-2 px-5 py-4 transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        className="tabular-nums"
                                        color="muted"
                                    >
                                        {eventWhen(event, displayTimezone)}
                                    </Text>
                                    <ContentStatusBadge
                                        type="event"
                                        status={event.status}
                                        label={event.status_label}
                                        isPublished={event.is_published}
                                    />
                                </div>

                                <Text as="span" variant="label" lineClamp={2}>
                                    {event.title}
                                </Text>

                                {event.location_name ? (
                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        color="muted"
                                        className="inline-flex items-center gap-1"
                                    >
                                        <MapPin
                                            className="size-3.5"
                                            aria-hidden="true"
                                        />
                                        {event.location_name}
                                    </Text>
                                ) : null}
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </Panel>
    );
}
