import { Head } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import type { PublicPastor, PublicPastorAssignment } from '../types';
import { ProfileHero } from '../components/profile-hero';
import { PastorCurrentSection } from '../components/pastor-current-section';
import { PastorHistorySection } from '../components/pastor-history-section';

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

            <ProfileHero
                ariaLabel="Profil Pastor Paroki"
                title="Pastor Paroki"
                description={
                    <Text as="p" variant="body" className="mt-5 max-w-lg">
                        Mengenal para imam yang melayani dan mendampingi umat
                        Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.
                    </Text>
                }
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
