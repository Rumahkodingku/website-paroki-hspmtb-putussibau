import { Head } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import { Seo } from '@/components/seo';
import type { PublicPastor, PublicPastorAssignment } from '../types';
import PublicHeroBanner from '@/components/public-hero-banner';
import { PastorCurrentSection } from '../components/pastor-current-section';
import { PastorHistorySection } from '../components/pastor-history-section';
import { home } from '@/routes';

type Props = {
    current: PublicPastor[];
    history: PublicPastorAssignment[];
};

export default function Pastor({ current, history }: Props) {
    return (
        <>
            <Seo
                title="Pastor Paroki"
                description="Pastor yang bertugas di Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau beserta riwayat penugasannya."
            />

            <Head title="Pastor Paroki" />

            <PublicHeroBanner
                eyebrow="PROFIL PAROKI"
                title="Pastor Paroki"
                description="Mengenal para imam yang melayani dan mendampingi umat Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Profil', href: home() },
                    { title: 'Pastor Paroki' },
                ]}
            />

            <PublicSection>
                <div className="space-y-14 sm:space-y-16">
                    <PastorCurrentSection current={current} />
                    <PastorHistorySection history={history} />
                </div>
            </PublicSection>
        </>
    );
}
