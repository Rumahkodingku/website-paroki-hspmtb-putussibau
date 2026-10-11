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
} from './components/location-form';
import type { FormMeta, LocationRow } from '../../types';

type Props = {
    location: LocationRow;
} & Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'types'>;

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
            <Head title={`Ubah Data Lokasi`} />

            <div className="p-6">
                <Heading
                    title={`Ubah Data Lokasi`}
                    description={`Lengkapi form untuk update data lokasi ${location.name}`}
                />
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
