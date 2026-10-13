import { Head } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { ProfileSubNav } from '../components/profile-subnav';
import { OrganizationChart } from '../components/organization-chart';
import type { PublicBoardPeriod } from '../types';

type Props = {
    periods: PublicBoardPeriod[];
};

export default function Struktur({ periods }: Props) {
    const active = periods.find((period) => period.is_active) ?? periods[0];
    const archive = periods.filter((period) => period.id !== active?.id);

    return (
        <>
            <Seo
                title="Struktur Kepengurusan"
                description="Susunan kepengurusan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau per periode."
            />

            <Head title="Struktur Kepengurusan" />

            <ProfileSubNav />

            <PublicSection>
                <div className="space-y-12">
                    <SectionHeading
                        eyebrow="Profil"
                        title="Struktur Kepengurusan"
                        description="Susunan pengurus paroki per periode."
                    />

                    {!active ? (
                        <Text
                            as="p"
                            variant="body"
                            color="muted"
                            className="rounded-lg border border-dashed border-hairline p-6"
                        >
                            Struktur kepengurusan belum dipublikasikan.
                        </Text>
                    ) : (
                        <section
                            aria-labelledby="periode-aktif"
                            className="space-y-6"
                        >
                            <div>
                                <Text as="h2" variant="h3" id="periode-aktif">
                                    {active.name}
                                </Text>
                                <Text
                                    as="p"
                                    variant="caption-strong"
                                    color="danger"
                                >
                                    Periode aktif · {active.start_year ?? '?'}–
                                    {active.end_year ?? 'sekarang'}
                                </Text>
                                {active.description ? (
                                    <Text
                                        as="p"
                                        variant="body"
                                        color="muted"
                                        className="mt-2 max-w-2xl"
                                    >
                                        {active.description}
                                    </Text>
                                ) : null}
                            </div>

                            <OrganizationChart members={active.members} />
                        </section>
                    )}

                    {archive.length > 0 ? (
                        <section className="space-y-8 border-t border-divider-soft pt-10">
                            <Text as="h2" variant="h3">
                                Arsip periode
                            </Text>

                            {archive.map((period) => (
                                <div key={period.id} className="space-y-4">
                                    <div>
                                        <Text as="h3" variant="h4">
                                            {period.name}
                                        </Text>
                                        <Text
                                            as="p"
                                            variant="caption"
                                            color="muted"
                                        >
                                            {period.start_year ?? '?'}–
                                            {period.end_year ?? 'sekarang'}
                                        </Text>
                                    </div>
                                    <OrganizationChart
                                        members={period.members}
                                    />
                                </div>
                            ))}
                        </section>
                    ) : null}
                </div>
            </PublicSection>
        </>
    );
}
