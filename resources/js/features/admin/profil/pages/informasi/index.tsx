import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { update as updateProfile } from '@/routes/parish-profile';
import {
    ParishProfileForm,
    parishProfileToFormData,
} from '@/features/admin/profil/pages/informasi/components/parish-profile-form';
import type { ParishProfileRow } from '@/features/admin/profil/types';

type Props = {
    profile: ParishProfileRow;
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Informasi({ profile, labels, hints }: Props) {
    return (
        <>
            <Head title="Informasi Profil" />

            <div className="p-6">
                <Heading
                    title="Informasi Profil"
                    description="Identitas dan deskripsi utama paroki yang tampil pada halaman /profil."
                    variant="default"
                />

                <ParishProfileForm
                    initial={parishProfileToFormData(profile)}
                    currentLogoUrl={profile.logo_url}
                    currentCoverUrl={profile.cover_url}
                    labels={labels}
                    hints={hints}
                    onSubmit={(form) => form.submit(updateProfile())}
                    onCancel={() => router.visit(updateProfile().url)}
                />
            </div>
        </>
    );
}

Informasi.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Informasi Profil' },
    ],
};
