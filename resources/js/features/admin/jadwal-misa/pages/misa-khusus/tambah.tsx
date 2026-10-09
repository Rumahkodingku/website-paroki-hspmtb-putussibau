import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as specialMassesIndex,
    store as storeSpecialMass,
} from '@/routes/special-masses';
import {
    SpecialMassForm,
    emptySpecialMassFormData,
} from '../../components/special-mass-form';
import type { FormMeta, LocationOption } from '../../types';

/*
 * /admin/misa-khusus/tambah — the create form for a one-off mass.
 */

type Props = Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'days'> & {
    locations: LocationOption[];
};

export default function Create({ labels, hints, statuses, locations }: Props) {
    return (
        <>
            <Head title="Tambah Misa Khusus" />

            <Heading title="Tambah Misa Khusus" variant="small" />

            <div className="p-6">
                <SpecialMassForm
                    initial={emptySpecialMassFormData()}
                    labels={labels}
                    hints={hints}
                    locations={locations}
                    statuses={statuses}
                    submitLabel="Simpan misa khusus"
                    onSubmit={(form) => form.submit(storeSpecialMass())}
                    onCancel={() => router.visit(specialMassesIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Misa Khusus', href: specialMassesIndex() },
        { title: 'Tambah' },
    ],
};
