import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as locationsIndex,
    store as storeLocation,
} from '@/routes/locations';
import {
    LocationForm,
    emptyLocationFormData,
} from './components/location-form';
import type { FormMeta } from '../../types';

type Props = Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'types'>;

export default function Create({ labels, hints, statuses, types }: Props) {
    return (
        <>
            <Head title="Tambah Lokasi" />

            <div className="p-6">
                <Heading
                    title="Tambah Lokasi"
                    description="Tambahkan lokasi jadwal misa disini"
                    variant="default"
                />

                <LocationForm
                    initial={emptyLocationFormData()}
                    labels={labels}
                    hints={hints}
                    types={types}
                    statuses={statuses}
                    submitLabel="Simpan lokasi"
                    onSubmit={(form) => form.submit(storeLocation())}
                    onCancel={() => router.visit(locationsIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa', href: locationsIndex() },
        { title: 'Lokasi' },
        { title: 'Tambah' },
    ],
};
