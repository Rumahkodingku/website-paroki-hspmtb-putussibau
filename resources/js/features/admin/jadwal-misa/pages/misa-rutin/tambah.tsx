import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as massSchedulesIndex,
    store as storeMassSchedule,
} from '@/routes/mass-schedules';
import {
    MassScheduleForm,
    emptyMassScheduleFormData,
} from './components/mass-schedule-form';
import type { FormMeta, LocationOption } from '../../types';

type Props = Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'days'> & {
    locations: LocationOption[];
};

export default function Create({
    labels,
    hints,
    statuses,
    days,
    locations,
}: Props) {
    return (
        <>
            <Head title="Tambah Jadwal Misa Rutin" />

            <div className="p-6">
                <Heading
                    title="Tambah Jadwal Misa Rutin"
                    description="Lengkapi form untuk menambahkan jadwal misa rutin"
                />

                <MassScheduleForm
                    initial={emptyMassScheduleFormData()}
                    labels={labels}
                    hints={hints}
                    days={days}
                    locations={locations}
                    statuses={statuses}
                    submitLabel="Simpan jadwal"
                    onSubmit={(form) => form.submit(storeMassSchedule())}
                    onCancel={() => router.visit(massSchedulesIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa', href: massSchedulesIndex() },
        { title: 'Tambah' },
    ],
};
