import { Head } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { HistoryTimeline } from '../components/history-timeline';
import type { PublicHistoryItem } from '../types';
import PublicHeroBanner from '@/components/public-hero-banner';
import { home } from '@/routes';

type Props = {
    narrative: string | null;
    items: PublicHistoryItem[];
};

export default function Sejarah({ items }: Props) {
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
                        { title: 'Profil', href: home() },
                        { title: 'Sejarah Paroki' },
                    ]}
                />

                {/* Timeline */}
                <section
                    aria-labelledby="history-timeline-title"
                    className="bg-background py-14 sm:py-16 lg:py-20"
                >
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
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
