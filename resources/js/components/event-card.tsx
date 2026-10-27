import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Text } from '@/components/text';
import { formatWibClock, formatWibDate } from '@/lib/datetime';
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

    return (
        <article
            className={`group relative flex flex-col gap-3 border-t border-border pt-5 ${
                isNotHappening ? 'opacity-70' : ''
            }`}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="space-y-0.5">
                    <Text as="p" variant="label">
                        {formatWibDate(event.start_at, displayTimezone)}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {event.is_all_day
                            ? 'Sepanjang hari'
                            : formatWibClock(event.start_at, displayTimezone)}
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
                Lihat Detail kegiatan
                <span className="sr-only">: {event.title}</span>
            </Link>
        </article>
    );
}
