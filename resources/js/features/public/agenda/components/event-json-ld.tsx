import { Head } from '@inertiajs/react';
import type { PublicEventDetail } from '../types';

/**
 * The activity, as schema.org/Event.
 *
 * Why it matters here specifically: an agenda is the one part of a parish
 * website that a congregation puts into their phone's calendar, and Google
 * reads events out of exactly this markup. Without it the parish's activities
 * are the only structured data on a site about a parish.
 *
 * Both calendars the page offers are generated from the same fields, so the
 * JSON-LD and the .ics can never disagree about when something starts.
 *
 * ## SSR
 *
 * Inertia SSR is off (D-26), so this script tag exists only in the document
 * after React hydrates. A crawler that executes JavaScript reads it; one that
 * does not sees no structured data at all. That is the same limitation the
 * Open Graph tags have, and AGENTS.md records why they are additionally
 * rendered in resources/views/app.blade.php. The difference is that a duplicated
 * OG tag is static per site while this one is per activity, so duplicating it
 * would mean plumbing the activity into the Blade view for no gain over what
 * Google already fetches.
 */
export function EventJsonLd({
    event,
    url,
    parishName,
}: {
    event: PublicEventDetail;
    url: string;
    /** Nullable in shared props; JSON-LD needs a string, so the caller falls back. */
    parishName: string | null;
}) {
    /*
     * eventStatus as a schema.org URL, not our own enum value.
     *
     * "cancelled" is not a schema.org EventStatusType — the vocabulary is
     * EventCancelled / EventPostponed / EventScheduled, which is the same
     * information but spelled the way a consumer expects. Postponed maps to
     * EventPostponed because schema.org has one and it is exactly right.
     */
    const eventStatus =
        event.status === 'cancelled'
            ? 'https://schema.org/EventCancelled'
            : event.status === 'postponed'
              ? 'https://schema.org/EventPostponed'
              : 'https://schema.org/EventScheduled';

    /*
     * An all-day activity has no clock. A startDate with a time on it would
     * make Google render it as beginning at midnight, which is the same
     * mistake the .ics writer would make if it wrote a timestamp.
     */
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
        // EVT-08: a cancelled activity is still an event, and saying so in
        // structured data is how the parish's own calendar tools can show it
        // struck through rather than dropping it.
        eventStatus,
        eventAttendanceMode: 'https://schema.org/OfflineEventAttendanceMode',
        url,
        // organization, not organizer: the parish is the publisher of the
        // page, and an organizer is whoever is running the event. schema.org
        // accepts a bare name here, and an empty one is worse than a generic
        // one, so an unset site name falls back rather than emitting "".
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
            // The maps URL rather than an address: a Location row carries
            // coordinates, not a street address, and schema.org rejects an
            // address object it cannot populate. sameAs is the honest way to
            // point at the coordinates.
            ...(event.location_url !== null
                ? { sameAs: event.location_url }
                : {}),
        };
    }

    return (
        <Head>
            {/*
             * head-key is what makes this replace rather than accumulate. Two
             * EventJsonLd components in one document — which happens for an
             * instant visit that renders before the old page unmounts — would
             * otherwise leave two Event blocks and a consumer would show the
             * parish's activity twice.
             */}
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
