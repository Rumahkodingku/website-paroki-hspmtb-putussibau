import { Head, router } from '@inertiajs/react';
import { dashboard } from '@/routes';
import { update as updateProfile } from '@/routes/parish-profile';
import {
    ProfileForm,
    profileToFormData,
} from '@/features/admin/profil/components/profile-form';
import type { ParishProfileRow } from '@/features/admin/profil/types';
import { ListHeader } from '@/components/list-parts';

type Props = {
    profile: ParishProfileRow;
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function InformasiProfil({ profile, labels, hints }: Props) {
    return (
        <>
            <Head title="Informasi Profil" />

            <div className="p-6">
                <ListHeader
                    title="Informasi Profil"
                    description="Konten profil paroki yang tampil pada halaman /profil."
                />

                <ProfileForm
                    initial={profileToFormData(profile)}
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

InformasiProfil.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Informasi Profil' },
    ],
};
