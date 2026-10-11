import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { index as eventsIndex, update as updateEvent } from '@/routes/events';
import type { LocationOption } from '@/types/location';
import type { Option } from '@/types/news-agenda';
import { toDateTimeLocalValue } from '@/lib/datetime';
import type { EventFormMeta, EventRow } from '../types';
import { EventForm, type EventFormInstance } from '../components/event-form';

type Props = {
    event: EventRow;
    eventCategories: Option[];
    locations: LocationOption[];
} & Pick<EventFormMeta, 'labels' | 'hints' | 'statuses' | 'eventStatusChoices'>;

export default function Edit({
    event,
    eventCategories,
    locations,
    labels,
    hints,
    statuses,
    eventStatusChoices,
}: Props) {
    const { displayTimezone } = usePage().props;

    /*
     * Stored in UTC; the inputs want a WIB wall clock.
     *
     * The end is the one that has to stay empty rather than become the epoch —
     * a finished activity with no end time is ordinary, and converting '' would
     * post 1970-01-01 and reach the ICS file as an event that ended before it
     * began.
     */
    const startAt = toDateTimeLocalValue(event.start_at, displayTimezone);

    const endAt =
        event.end_at === null
            ? ''
            : toDateTimeLocalValue(event.end_at, displayTimezone);

    return (
        <>
            <Head title="Ubah Kegiatan" />

            <div className="p-6">
                <Heading
                    title="Ubah Kegiatan"
                    description={`Lengkapi form untuk kegiatan ${event.title}.`}
                />

                <EventForm
                    initial={{
                        title: event.title,
                        // Sent back as typed, for the reason in post-form.tsx:
                        // a slug that is already in a shared URL must not move
                        // because somebody reworded the title.
                        slug: event.slug,
                        event_category_id: event.event_category_id,
                        description: event.description ?? '',
                        start_at: startAt,
                        end_at: endAt,
                        is_all_day: event.is_all_day,
                        location_id: event.location_id,
                        // Cleared when a master location is chosen, because
                        // EVT-06 says the master name wins. Sending both would
                        // make the public page render whichever the resource
                        // happened to prefer, and the admin could not tell.
                        location_name:
                            event.location_id === null
                                ? (event.location_name ?? '')
                                : '',
                        poster_alt: event.poster_alt ?? '',
                        status: event.status,
                        is_published: event.is_published,
                        media_id: event.media_id,
                    }}
                    labels={labels}
                    hints={hints}
                    statuses={statuses}
                    statusChoices={eventStatusChoices}
                    categories={eventCategories}
                    locations={locations}
                    currentPoster={event.poster_path}
                    displayTimezone={displayTimezone}
                    submitLabel="Simpan perubahan"
                    onSubmit={(form: EventFormInstance) =>
                        form.submit(updateEvent(event.id))
                    }
                    onCancel={() => router.visit(eventsIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Agenda', href: eventsIndex() },
        { title: 'Ubah' },
    ],
};
