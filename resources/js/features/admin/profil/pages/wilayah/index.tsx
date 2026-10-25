import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { update as updateServiceArea } from '@/routes/parish-service-area';
import {
    ServiceAreaForm,
    serviceAreaToFormData,
} from '@/features/admin/profil/components/service-area-form';
import type { ParishServiceAreaRow } from '@/features/admin/profil/types';

type Props = {
    area: ParishServiceAreaRow;
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Wilayah({ area, labels, hints }: Props) {
    return (
        <>
            <Head title="Wilayah Pelayanan" />

            <div className="p-6">
                <Heading
                    title="Wilayah Pelayanan"
                    description="Narasi cakupan wilayah pelayanan paroki yang tampil pada halaman /profil/wilayah."
                    variant="default"
                />

                <ServiceAreaForm
                    initial={serviceAreaToFormData(area)}
                    currentMapUrl={area.map_media_url}
                    labels={labels}
                    hints={hints}
                    onSubmit={(form) => form.submit(updateServiceArea())}
                    onCancel={() => router.visit(updateServiceArea().url)}
                />
            </div>
        </>
    );
}

Wilayah.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Wilayah Pelayanan' },
    ],
};
