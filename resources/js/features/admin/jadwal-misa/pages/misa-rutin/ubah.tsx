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
import { MassScheduleForm } from '../../components/mass-schedule-form';
import type { FormMeta, LocationOption, MassScheduleRow } from '../../types';

/*
 * /admin/jadwal-misa/{mass_schedule}/ubah — the edit form for a recurring mass.
 *
 * Also the entry point for duplication (PRD 7.3), which is here rather than on
 * the list because the destination picker needs the schedule's own day and time
 * to say whether the copy is even possible.
 */

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

    // The duplicate request carries only a destination; the day, hour and names
    // come from the record being copied, so the backend can check whether the
    // destination has room for them before anything is written.
    const duplicateForm = useForm({ location_id: '' });

    return (
        <>
            <Head title={`Ubah ${massSchedule.name}`} />

            <Heading title={`Ubah ${massSchedule.name}`} variant="small" />

            <div className="p-6">
                <MassScheduleForm
                    initial={{
                        location_id: String(massSchedule.location_id),
                        day_of_week: String(massSchedule.day_of_week),
                        start_time: massSchedule.start_time,
                        name: massSchedule.name,
                        language: massSchedule.language ?? '',
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

/*
 * `Component.layout` is a static field, evaluated at module load, so a crumb
 * cannot carry the record's name — it would be whatever the first page built
 * happened to be. The list is one level up and the page title, which is derived
 * from the record, carries the name instead.
 */
Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa', href: massSchedulesIndex() },
        { title: 'Ubah' },
    ],
};
