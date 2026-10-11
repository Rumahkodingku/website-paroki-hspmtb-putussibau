import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as specialMassesIndex,
    update as updateSpecialMass,
} from '@/routes/special-masses';
import {
    SpecialMassForm,
    specialMassFormDataFrom,
} from './components/special-mass-form';
import type { FormMeta, LocationOption, SpecialMassRow } from '../../types';

type Props = {
    specialMass: SpecialMassRow;
} & Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'days'> & {
        locations: LocationOption[];
    };

export default function Edit({
    specialMass,
    labels,
    hints,
    statuses,
    locations,
}: Props) {
    const { displayTimezone } = usePage().props;

    return (
        <>
            <Head title={`Ubah data misa khusus`} />

            <div className="p-6">
                <Heading
                    title={`Ubah data misa khusus`}
                    description={`Lengkapi form untuk update data ${specialMass.title}`}
                />
                <SpecialMassForm
                    initial={specialMassFormDataFrom(
                        specialMass,
                        displayTimezone,
                    )}
                    labels={labels}
                    hints={hints}
                    locations={locations}
                    statuses={statuses}
                    submitLabel="Simpan perubahan"
                    onSubmit={(form) =>
                        form.submit(updateSpecialMass(specialMass.id))
                    }
                    onCancel={() => router.visit(specialMassesIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Misa Khusus', href: specialMassesIndex() },
        { title: 'Ubah' },
    ],
};
