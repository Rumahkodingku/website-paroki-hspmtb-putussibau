import { usePage } from '@inertiajs/react';
import { JsonLd } from '@/components/json-ld';
import type { PublicEventDetail } from '../types';
import type { SeoDefaults } from '@/types';
import { absoluteJsonLdUrl } from '@/lib/json-ld';

export function EventJsonLd({ event }: { event: PublicEventDetail }) {
    const { seo } = usePage<{ seo: SeoDefaults }>().props;
    const canonical = seo.page.canonical;

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
        url: canonical,
        organization: {
            '@type': 'Organization',
            name: seo.siteName ?? 'Paroki HSPMTB Putussibau',
            url: seo.appUrl,
        },
    };

    if (endDate !== null) {
        data.endDate = endDate;
    }

    if (event.plain_description !== null) {
        data.description = event.plain_description;
    }

    const image = absoluteJsonLdUrl(event.poster, seo.appUrl);

    if (image !== null) {
        data.image = image;
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

    return <JsonLd data={data} headKey="event-json-ld" />;
}
