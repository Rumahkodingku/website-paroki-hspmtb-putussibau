import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as communitiesIndex,
    store as storeCommunity,
} from '@/routes/communities';
import {
    CommunityForm,
    emptyCommunityFormData,
} from '../components/community-form';
import type { CategoryOption, StatusOption } from '../types';

type Props = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, unknown>;
    statuses: StatusOption[];
    categories: CategoryOption[];
};

export default function Create({
    labels,
    hints,
    copy,
    statuses,
    categories,
}: Props) {
    return (
        <>
            <Head title="Tambah Komunitas" />

            <div className="space-y-6 p-6">
                <Heading
                    title="Tambah Komunitas"
                    description="Buat profil komunitas baru, lengkapi visi, jadwal, dan kontaknya."
                />

                <CommunityForm
                    initial={emptyCommunityFormData()}
                    labels={labels}
                    hints={hints}
                    copy={copy}
                    statuses={statuses}
                    categories={categories}
                    submitLabel="Simpan komunitas"
                    onSubmit={(form) => form.submit(storeCommunity())}
                    onCancel={() => router.visit(communitiesIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Komunitas', href: communitiesIndex() },
        { title: 'Tambah' },
    ],
};
