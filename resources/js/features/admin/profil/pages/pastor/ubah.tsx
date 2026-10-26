import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as pastorsIndex,
    update as updatePastor,
} from '@/routes/pastors';
import type { PastorRow } from '@/features/admin/profil/types';
import { PastorForm, pastorToFormData } from './components/pastor-form';

type Props = {
    pastor: PastorRow;
    labels: Record<string, string>;
    hints: Record<string, string>;
    positions: Array<{ value: string; label: string }>;
};

export default function Edit({ pastor, labels, hints }: Props) {
    return (
        <>
            <Head title={`Ubah: ${pastor.name}`} />

            <div className="space-y-6 p-6">
                <section>
                    <Heading
                        title="Ubah Pastor"
                        description={pastor.name}
                        variant="default"
                    />

                    <PastorForm
                        initial={pastorToFormData(pastor)}
                        currentPhotoUrl={pastor.photo_url}
                        labels={labels}
                        hints={hints}
                        onSubmit={(form) =>
                            form.submit(updatePastor({ pastor: pastor.id }))
                        }
                        onCancel={() => router.visit(pastorsIndex())}
                    />
                </section>
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Pastor Paroki' },
        { title: 'Ubah' },
    ],
};
