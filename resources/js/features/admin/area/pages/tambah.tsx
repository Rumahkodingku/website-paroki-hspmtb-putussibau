import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { index as areasIndex, store as storeArea } from '@/routes/areas';
import { AreaForm, emptyAreaFormData } from '../components/area-form';
import type { AreaOption, AreaTypeOption } from '../types';

type Props = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    areaTypes: AreaTypeOption[];
    parents: AreaOption[];
};

export default function Create({ labels, hints, areaTypes, parents }: Props) {
    return (
        <>
            <Head title="Tambah Stasi/Lingkungan" />

            <div className="p-6">
                <Heading
                    title="Tambah Stasi/Lingkungan"
                    description="Tambahkan stasi, lingkungan, atau wilayah."
                />

                <AreaForm
                    initial={emptyAreaFormData()}
                    labels={labels}
                    hints={hints}
                    areaTypes={areaTypes}
                    parents={parents}
                    submitLabel="Simpan area"
                    onSubmit={(form) => form.submit(storeArea())}
                    onCancel={() => router.visit(areasIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Area', href: areasIndex() },
        { title: 'Tambah' },
    ],
};
