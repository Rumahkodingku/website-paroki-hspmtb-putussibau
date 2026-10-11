import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    create as createEvent,
    index as eventsIndex,
    store as storeEvent,
} from '@/routes/events';
import type { LocationOption } from '@/types/location';
import type { Option } from '@/types/news-agenda';
import type { EventFormMeta } from '../types';
import {
    emptyEventFormData,
    EventForm,
    type EventFormInstance,
} from '../components/event-form';

type Props = {
    eventCategories: Option[];
    locations: LocationOption[];
} & Pick<EventFormMeta, 'labels' | 'hints' | 'statuses' | 'eventStatusChoices'>;

export default function Create({
    eventCategories,
    locations,
    labels,
    hints,
    statuses,
    eventStatusChoices,
}: Props) {
    const { displayTimezone } = usePage().props;

    return (
        <>
            <Head title="Tambah Kegiatan" />

            <div className="p-6">
                <Heading
                    title="Tambah Kegiatan"
                    description="Tentukan waktu, tempat, dan apakah kegiatan diumumkan ke publik."
                />

                <EventForm
                    initial={emptyEventFormData(eventCategories, locations)}
                    labels={labels}
                    hints={hints}
                    statuses={statuses}
                    statusChoices={eventStatusChoices}
                    categories={eventCategories}
                    locations={locations}
                    currentPoster={null}
                    displayTimezone={displayTimezone}
                    submitLabel="Simpan kegiatan"
                    onSubmit={(form: EventFormInstance) =>
                        form.submit(storeEvent())
                    }
                    onCancel={() => router.visit(eventsIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Agenda', href: createEvent() },
        { title: 'Tambah' },
    ],
};
