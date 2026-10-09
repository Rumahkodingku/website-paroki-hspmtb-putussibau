import { Head, router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as locationsIndex,
    update as updateLocation,
} from '@/routes/locations';
import {
    LocationForm,
    type LocationFormInstance,
} from '../../components/location-form';
import type { FormMeta, LocationRow } from '../../types';

/*
 * /admin/lokasi/{location}/ubah — the edit form.
 *
 * One thing happens here that the shared form does not: a save that would
 * switch an active location off while its schedules are still active asks
 * first (MASS-07).
 *
 * The dialog is a courtesy, not the guarantee. If the browser never asked, those
 * schedules would still stop being published — Location::scopeActive and
 * MassSchedule::scopeActive decide that on the server, which is what makes
 * MASS-07 hold for a request that skipped this form entirely. The dialog exists
 * so the admin is not surprised, not so the rule is enforced.
 *
 * is_active is an ordinary field rather than a separate endpoint (D-6): nothing
 * is deleted in this module, so deactivating is the whole lifecycle.
 */

type Props = {
    location: LocationRow;
} & Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'types'>;

/** The submitted callback, held so the dialog can run it on confirmation. */
type PendingSubmit = { submit: () => void };

export default function Edit({
    location,
    labels,
    hints,
    statuses,
    types,
}: Props) {
    const [confirming, setConfirming] = useState(false);
    const pending = useRef<PendingSubmit | null>(null);

    const activeScheduleCount = location.active_schedules_count ?? 0;

    return (
        <>
            <Head title={`Ubah ${location.name}`} />

            <Heading title={`Ubah ${location.name}`} variant="small" />

            <div className="p-6">
                <LocationForm
                    initial={{
                        name: location.name,
                        slug: location.slug,
                        type: location.type,
                        address: location.address ?? '',
                        latitude: location.latitude ?? '',
                        longitude: location.longitude ?? '',
                        maps_url: location.maps_url ?? '',
                        notes: location.notes ?? '',
                        is_active: location.is_active,
                    }}
                    labels={labels}
                    hints={hints}
                    types={types}
                    statuses={statuses}
                    submitLabel="Simpan perubahan"
                    onSubmit={(form: LocationFormInstance) => {
                        const hiding =
                            location.is_active &&
                            !form.data.is_active &&
                            activeScheduleCount > 0;

                        if (hiding) {
                            pending.current = {
                                submit: () =>
                                    form.submit(updateLocation(location.id)),
                            };
                            setConfirming(true);

                            return;
                        }

                        form.submit(updateLocation(location.id));
                    }}
                    onCancel={() => router.visit(locationsIndex())}
                />
            </div>

            <ConfirmDialog
                open={confirming}
                onOpenChange={setConfirming}
                title="Nonaktifkan lokasi ini?"
                description={`${location.name} masih memiliki ${activeScheduleCount} jadwal aktif. Jadwal pada lokasi nonaktif tidak akan tampil di halaman publik. Data tidak dihapus.`}
                confirmLabel="Ya, nonaktifkan"
                destructive
                onConfirm={() => {
                    setConfirming(false);
                    pending.current?.submit();
                    pending.current = null;
                }}
            />
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa', href: locationsIndex() },
        { title: 'Lokasi' },
        { title: 'Ubah' },
    ],
};
