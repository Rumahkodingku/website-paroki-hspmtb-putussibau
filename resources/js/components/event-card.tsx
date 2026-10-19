import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Text } from '@/components/text';
import { formatWibDate, formatWibTime } from '@/lib/datetime';
import { show as eventShow } from '@/routes/agenda';

/**
 * One activity, as the public agenda and homepage cards need it.
 *
 * The type lives beside the component because two features render it now; a
 * feature's own resource shape is structurally compatible.
 */
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

/**
 * One activity, as a card.
 *
 * The date leads because that is what the reader is looking for. DESIGN.md's
 * NewsCard and AgendaCard both put the date in the same corner, and on a parish
 * agenda the date is the whole of what distinguishes one entry from another —
 * nobody opens an agenda page hoping to read a description.
 *
 * An all-day activity prints a date and no clock. Printing "00:00" would be a
 * lie the reader has to notice and discount.
 */
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

    return (
        <article
            className={`group relative flex flex-col gap-3 border-t border-border pt-5 ${
                isNotHappening ? 'opacity-70' : ''
            }`}
        >
            {/*
             * A cancelled activity keeps its slot in the list. EVT-08 asks for
             * exactly that: a cancelled activity stays on the calendar and says
             * so, because somebody reading the parish's calendar at 6am needs
             * to learn it is off, not to find the entry quietly missing. The
             * dimming plus the badge is decoration on top of the word, never
             * instead of it — roadmap 4.1 forbids signalling by colour alone.
             */}
            <div className="flex items-start justify-between gap-3">
                <div className="space-y-0.5">
                    <Text as="p" variant="label" color="brand">
                        {formatWibDate(event.start_at, displayTimezone)}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {event.is_all_day
                            ? 'Sepanjang hari'
                            : formatWibTime(event.start_at)}
                    </Text>
                </div>

                {isNotHappening ? (
                    <Badge
                        variant={
                            event.status === 'cancelled'
                                ? 'destructive'
                                : 'outline'
                        }
                    >
                        {event.status_label}
                    </Badge>
                ) : null}
            </div>

            <Text
                as={headingLevel}
                variant={headingLevel === 'h2' ? 'h2' : 'h3'}
            >
                {event.title}
            </Text>

            {event.location_name ? (
                <Text as="p" variant="body-sm" color="muted">
                    {event.location_name}
                </Text>
            ) : null}

            {event.summary ? (
                <Text as="p" variant="body">
                    {event.summary}
                </Text>
            ) : null}

            <Link
                href={eventShow(event.slug)}
                prefetch
                className="rounded-sm text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                Detail kegiatan
                <span className="sr-only">: {event.title}</span>
            </Link>
        </article>
    );
}
