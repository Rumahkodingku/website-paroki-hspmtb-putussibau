import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { index as pastorsIndex, store as storePastor } from '@/routes/pastors';
import { emptyPastorFormData, PastorForm } from './components/pastor-form';

type Props = {
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Create({ labels, hints }: Props) {
    return (
        <>
            <Head title="Tambah Pastor" />

            <div className="p-6">
                <Heading
                    title="Tambah Pastor"
                    description="Data identitas pastor. Riwayat penugasan ditambahkan setelah tersimpan."
                    variant="default"
                />

                <PastorForm
                    initial={emptyPastorFormData()}
                    currentPhotoUrl={null}
                    labels={labels}
                    hints={hints}
                    onSubmit={(form) => form.submit(storePastor())}
                    onCancel={() => router.visit(pastorsIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Pastor Paroki' },
        { title: 'Tambah' },
    ],
};
