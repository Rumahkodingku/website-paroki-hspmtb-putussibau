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
} from './components/special-mass-form';
import type { FormMeta, LocationOption } from '../../types';

type Props = Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'days'> & {
    locations: LocationOption[];
};

export default function Create({ labels, hints, statuses, locations }: Props) {
    return (
        <>
            <Head title="Tambah Misa Khusus" />

            <div className="p-6">
                <Heading
                    title="Tambah Misa Khusus"
                    description="Lengkapi form untuk menambahkan misa khusus baru"
                />
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
