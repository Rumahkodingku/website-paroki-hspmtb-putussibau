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
} from '../../components/special-mass-form';
import type { FormMeta, LocationOption, SpecialMassRow } from '../../types';

/*
 * /admin/misa-khusus/{special_mass}/ubah — the edit form.
 *
 * The two datetimes arrive as UTC and are rendered into the form in WIB, which
 * is the wall clock the admin originally typed. Doing that conversion with an
 * explicit zone rather than the device's is D-01's whole point: an admin whose
 * laptop is set to another timezone would otherwise see the mass moved by the
 * difference.
 */

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
            <Head title={`Ubah ${specialMass.title}`} />

            <Heading title={`Ubah ${specialMass.title}`} variant="small" />

            <div className="p-6">
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
