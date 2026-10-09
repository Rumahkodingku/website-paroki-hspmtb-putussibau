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
} from '../../components/location-form';
import type { FormMeta } from '../../types';

/*
 * /admin/lokasi/tambah — the create form.
 *
 * The form component is shared with the edit page, so this file is only the
 * page: the props it needs, the endpoint it posts to, and the layout it sits
 * in. ARCHITECTURE.md Part B section 3 says a page is thin on purpose.
 *
 * Imports reach two levels up because the component lives at the feature root
 * while the page lives at pages/lokasi/ — the same shape as
 * features/admin/akun/pages/profile.tsx importing from components/.
 */

type Props = Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'types'>;

export default function Create({ labels, hints, statuses, types }: Props) {
    return (
        <>
            <Head title="Tambah Lokasi" />

            <Heading title="Tambah Lokasi" variant="small" />

            <div className="p-6">
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
