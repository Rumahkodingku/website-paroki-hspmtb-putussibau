import { Head } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { RichText } from '@/components/rich-text';
import { Text } from '@/components/text';
import { HistoryTimeline } from '../components/history-timeline';
import type { PublicHistoryItem } from '../types';
import PublicHeroBanner from '@/components/public-hero-banner';
import { home } from '@/routes';
import { index as profilIndex } from '@/routes/profil';
import SectionHeading from '@/components/section-heading';

type Props = {
    narrative: string | null;
    items: PublicHistoryItem[];
};

export default function Sejarah({ narrative, items }: Props) {
    return (
        <>
            <Seo
                title="Sejarah Paroki"
                description="Narasi dan linimasa sejarah Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
            />

            <Head title="Sejarah Paroki" />

            <main>
                <PublicHeroBanner
                    eyebrow="PROFIL PAROKI"
                    title="Sejarah Paroki"
                    description="Mengenal perjalanan iman umat dan perkembangan pelayanan Paroki HSPMTB Putussibau dari masa ke masa."
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Profil', href: profilIndex() },
                        { title: 'Sejarah Paroki' },
                    ]}
                />

                {narrative?.trim() ? (
                    <section
                        aria-labelledby="history-narrative-title"
                        className="bg-background py-10 sm:py-12"
                    >
                        <div className="mx-auto max-w-7xl px-5 text-start sm:px-8">
                            <div className="border-t border-border/60 pt-8">
                                <RichText
                                    html={narrative}
                                    className="text-base leading-relaxed [&_p]:mb-4 [&_p:last-child]:mb-0"
                                />
                            </div>
                        </div>
                    </section>
                ) : null}

                {/* Timeline */}
                <section
                    aria-labelledby="history-timeline-title"
                    className="bg-background py-14 sm:py-16 lg:py-20"
                >
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                        <SectionHeading
                            eyebrow="Linimasa"
                            title="Linimasa sejarah paroki"
                        />

                        {items.length === 0 ? (
                            <Text
                                as="p"
                                variant="body"
                                color="muted"
                                className="rounded-xl border border-dashed border-border p-6"
                            >
                                Linimasa sejarah belum dipublikasikan.
                            </Text>
                        ) : (
                            <HistoryTimeline items={items} />
                        )}
                    </div>
                </section>
            </main>
        </>
    );
}
