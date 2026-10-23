import { Head } from '@inertiajs/react';
import type { PublicEventDetail } from '../types';

export function EventJsonLd({
    event,
    url,
    parishName,
}: {
    event: PublicEventDetail;
    url: string;
    parishName: string | null;
}) {
    const eventStatus =
        event.status === 'cancelled'
            ? 'https://schema.org/EventCancelled'
            : event.status === 'postponed'
              ? 'https://schema.org/EventPostponed'
              : 'https://schema.org/EventScheduled';

    const startDate = event.is_all_day
        ? event.start_at.slice(0, 10)
        : event.start_at;

    const endDate = event.is_all_day
        ? event.end_at
            ? event.end_at.slice(0, 10)
            : null
        : event.end_at;

    const data: Record<string, unknown> = {
        '@context': 'https://schema.org',
        '@type': 'Event',
        name: event.title,
        startDate,
        eventStatus,
        eventAttendanceMode: 'https://schema.org/OfflineEventAttendanceMode',
        url,
        organization: {
            '@type': 'Organization',
            name:
                parishName ??
                'Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau',
        },
    };

    if (endDate !== null) {
        data.endDate = endDate;
    }

    if (event.plain_description !== null) {
        data.description = event.plain_description;
    }

    if (event.poster !== null) {
        data.image = event.poster;
    }

    if (event.category !== null) {
        data.about = { '@type': 'Thing', name: event.category.name };
    }

    if (event.location_name !== null) {
        data.location = {
            '@type': 'Place',
            name: event.location_name,
            ...(event.location_url !== null
                ? { sameAs: event.location_url }
                : {}),
        };
    }

    return (
        <Head>
            <script
                type="application/ld+json"
                data-head-key="event-json-ld"
                dangerouslySetInnerHTML={{
                    __html: JSON.stringify(data),
                }}
            />
        </Head>
    );
}
