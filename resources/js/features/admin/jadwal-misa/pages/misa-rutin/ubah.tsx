import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { formatDayName, formatWibTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import {
    duplicate as duplicateMassSchedule,
    index as massSchedulesIndex,
    update as updateMassSchedule,
} from '@/routes/mass-schedules';
import { DuplicateMassScheduleDialog } from '../../components/duplicate-mass-schedule-dialog';
import { MassScheduleForm } from './components/mass-schedule-form';
import type { FormMeta, LocationOption, MassScheduleRow } from '../../types';

type Props = {
    massSchedule: MassScheduleRow;
} & Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'days'> & {
        locations: LocationOption[];
    };

export default function Edit({
    massSchedule,
    labels,
    hints,
    statuses,
    days,
    locations,
}: Props) {
    const [duplicating, setDuplicating] = useState(false);
    const duplicateForm = useForm({ location_id: '' });

    return (
        <>
            <Head title={`Ubah data misa rutin`} />

            <div className="p-6">
                <Heading
                    title={`Ubah data misa rutin`}
                    description={`Lengkapi form untuk update data ${massSchedule.name}`}
                />

                <MassScheduleForm
                    initial={{
                        location_id: String(massSchedule.location_id),
                        day_of_week: String(massSchedule.day_of_week),
                        start_time: massSchedule.start_time,
                        name: massSchedule.name,
                        notes: massSchedule.notes ?? '',
                        is_active: massSchedule.is_active,
                    }}
                    labels={labels}
                    hints={hints}
                    days={days}
                    locations={locations}
                    statuses={statuses}
                    submitLabel="Simpan perubahan"
                    onSubmit={(instance) =>
                        instance.submit(updateMassSchedule(massSchedule.id))
                    }
                    onCancel={() => router.visit(massSchedulesIndex())}
                    onDuplicate={() => setDuplicating(true)}
                />
            </div>

            <DuplicateMassScheduleDialog
                open={duplicating}
                onOpenChange={setDuplicating}
                locations={locations}
                scheduleName={massSchedule.name}
                scheduleTime={formatWibTime(massSchedule.start_time)}
                dayLabel={
                    days.find((day) => day.value === massSchedule.day_of_week)
                        ?.label ?? formatDayName(massSchedule.day_of_week)
                }
                isSubmitting={duplicateForm.processing}
                errors={duplicateForm.errors}
                onSubmit={(locationId) => {
                    duplicateForm.setData('location_id', String(locationId));
                    duplicateForm.submit(
                        duplicateMassSchedule(massSchedule.id),
                        { preserveScroll: true },
                    );
                }}
            />
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa', href: massSchedulesIndex() },
        { title: 'Ubah' },
    ],
};
