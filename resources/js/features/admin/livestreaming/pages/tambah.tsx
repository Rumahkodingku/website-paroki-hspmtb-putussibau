import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as livestreamsIndex,
    store as storeLivestream,
} from '@/routes/livestreams';
import {
    LivestreamForm,
    emptyLivestreamFormData,
    submitLivestreamForm,
} from '../components/livestream-form';
import type { LivestreamFormMeta, LocationOption } from '../types';

type Props = LivestreamFormMeta & {
    locations: LocationOption[];
};

export default function Create({
    labels,
    hints,
    copy,
    categories,
    statuses,
    locations,
}: Props) {
    return (
        <>
            <Head title="Tambah Livestreaming" />

            <div className="space-y-6 p-6">
                <Heading
                    title={copy.add ?? 'Tambah Livestreaming'}
                    description="Simpan metadata siaran, lalu terbitkan dan mulai saat stream benar-benar berjalan di YouTube."
                />

                <LivestreamForm
                    initial={emptyLivestreamFormData()}
                    labels={labels}
                    hints={hints}
                    copy={copy}
                    categories={categories}
                    statuses={statuses}
                    locations={locations}
                    submitLabel={copy.save ?? 'Simpan'}
                    onSubmit={(form) =>
                        submitLivestreamForm(form, storeLivestream())
                    }
                    onCancel={() => router.visit(livestreamsIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Livestreaming', href: livestreamsIndex() },
        { title: 'Tambah' },
    ],
};
