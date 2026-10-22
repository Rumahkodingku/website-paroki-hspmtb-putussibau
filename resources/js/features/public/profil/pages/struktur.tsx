import { Head } from '@inertiajs/react';
import { SectionHeading } from '@/components/section-heading';
import { Text } from '@/components/text';
import { Seo } from '@/components/seo';
import { PublicSection } from '@/components/public-section';
import { PublicBoardPeriod } from '../types';
import { OrganizationChart } from '../components/organization-chart';
import { BoardPeriodArchive } from '../components/board-period-archive';
import PublicHeroBanner from '@/components/public-hero-banner';
import { home } from '@/routes';

type Props = {
    periods: PublicBoardPeriod[];
};

export default function Struktur({ periods }: Props) {
    const active = periods.find((period) => period.is_active);

    const archive = periods
        .filter((period) => !period.is_active)
        .slice()
        .sort((a, b) => (b.start_year ?? 0) - (a.start_year ?? 0));

    return (
        <>
            <Seo
                title="Struktur Kepengurusan"
                description="Susunan kepengurusan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau per periode."
            />

            <Head title="Struktur Kepengurusan" />

            <PublicHeroBanner
                eyebrow="PROFIL PAROKI"
                title="Struktur Kepengurusan"
                description="Susunan pengurus Dewan Pastoral Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau dalam setiap periode pelayanan."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Profil', href: home() },
                    { title: 'Struktur Kepengurusan' },
                ]}
            />

            <PublicSection>
                <div className="space-y-12 py-4 sm:space-y-16">
                    {!active ? (
                        <Text
                            as="p"
                            variant="body"
                            color="muted"
                            className="rounded-xl border border-dashed border-border p-6 sm:p-8"
                        >
                            Struktur kepengurusan belum dipublikasikan.
                        </Text>
                    ) : (
                        <section
                            aria-labelledby="periode-aktif"
                            className="space-y-7"
                        >
                            <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                                <div className="min-w-0 space-y-3">
                                    <SectionHeading
                                        title={active.name}
                                        description={
                                            active?.description ?? undefined
                                        }
                                        level={2}
                                        className="gap-3 sm:gap-4"
                                    />
                                </div>
                            </div>

                            <OrganizationChart members={active.members} />
                        </section>
                    )}

                    <BoardPeriodArchive periods={archive} />
                </div>
            </PublicSection>
        </>
    );
}
