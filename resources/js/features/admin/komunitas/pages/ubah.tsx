import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as communitiesIndex,
    update as updateCommunity,
} from '@/routes/communities';
import { CommunityForm } from '../components/community-form';
import type { CommunityFormInstance } from '../components/community-form-instance';
import type {
    CategoryOption,
    CommunityDetail,
    CommunityFormData,
    StatusOption,
} from '../types';

type Props = {
    community: CommunityDetail;
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, unknown>;
    statuses: StatusOption[];
    categories: CategoryOption[];
};

export default function Edit({
    community,
    labels,
    hints,
    copy,
    statuses,
    categories,
}: Props) {
    const initial: CommunityFormData = {
        name: community.name,
        slug: community.slug,
        community_category_id: String(community.community_category_id),
        logo_media_id: community.logo_media_id,
        logo_alt: community.logo_alt ?? '',
        thumbnail_media_id: community.thumbnail_media_id,
        thumbnail_alt: community.thumbnail_alt ?? '',
        description: community.description ?? '',
        vision: community.vision ?? '',
        mission: community.mission ?? '',
        meeting_schedule: community.meeting_schedule ?? '',
        meeting_location: community.meeting_location ?? '',
        eligibility: community.eligibility ?? '',
        joining_info: community.joining_info ?? '',
        contact_name: community.contact_name ?? '',
        contact_phone: community.contact_phone ?? '',
        show_contact: community.show_contact,
        facebook: community.facebook ?? '',
        instagram: community.instagram ?? '',
        tiktok: community.tiktok ?? '',
        youtube: community.youtube ?? '',
        website: community.website ?? '',
        status: community.status,
        is_published: community.is_published,
    };

    const submit = (form: CommunityFormInstance) => {
        form.submit(updateCommunity(community.id));
    };

    return (
        <>
            <Head title={`Ubah ${community.name}`} />

            <div className="space-y-6 p-6">
                <Heading
                    title="Ubah Komunitas"
                    description={`Perbarui profil komunitas "${community.name}".`}
                />

                <CommunityForm
                    initial={initial}
                    labels={labels}
                    hints={hints}
                    copy={copy}
                    statuses={statuses}
                    categories={categories}
                    logoUrl={community.logo_url}
                    thumbnailUrl={community.thumbnail_url}
                    submitLabel="Simpan perubahan"
                    onSubmit={submit}
                    onCancel={() => router.visit(communitiesIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Komunitas', href: communitiesIndex() },
        { title: 'Ubah' },
    ],
};
