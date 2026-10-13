import { Head } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { ProfileSubNav } from '../components/profile-subnav';
import { PastorCard } from '../components/pastor-card';
import type { PublicPastor, PublicPastorAssignment } from '../types';

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

            <ProfileSubNav />

            <PublicSection>
                <div className="space-y-12">
                    <SectionHeading
                        eyebrow="Profil"
                        title="Pastor Paroki"
                        description="Pastor yang sedang bertugas dan riwayat penugasan."
                    />

                    <section
                        aria-labelledby="pastor-bertugas"
                        className="space-y-6"
                    >
                        <Text as="h2" variant="h3" id="pastor-bertugas">
                            Pastor yang bertugas
                        </Text>

                        {current.length === 0 ? (
                            <Text
                                as="p"
                                variant="body"
                                color="muted"
                                className="rounded-lg border border-dashed border-hairline p-6"
                            >
                                Data pastor yang bertugas belum dipublikasikan.
                            </Text>
                        ) : (
                            <div className="space-y-8">
                                {current.map((pastor) => (
                                    <PastorCard
                                        key={pastor.id}
                                        pastor={pastor}
                                    />
                                ))}
                            </div>
                        )}
                    </section>

                    {history.length > 0 ? (
                        <section
                            aria-labelledby="riwayat-pastor"
                            className="space-y-6 border-t border-divider-soft pt-10"
                        >
                            <Text as="h2" variant="h3" id="riwayat-pastor">
                                Riwayat pastor paroki
                            </Text>

                            <ul className="space-y-4">
                                {history.map((assignment) => (
                                    <li
                                        key={assignment.id}
                                        className="flex flex-wrap items-baseline justify-between gap-2 border-b border-hairline pb-3"
                                    >
                                        <Text as="span" variant="body-strong">
                                            {assignment.title
                                                ? `${assignment.title} ${assignment.name}`
                                                : assignment.name}
                                        </Text>
                                        <Text
                                            as="span"
                                            variant="caption"
                                            color="muted"
                                        >
                                            {assignment.position_label} ·{' '}
                                            {assignment.start_year ??
                                                'Tahun tidak diketahui'}
                                            {assignment.end_year
                                                ? `–${assignment.end_year}`
                                                : '–sekarang'}
                                        </Text>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ) : null}
                </div>
            </PublicSection>
        </>
    );
}
