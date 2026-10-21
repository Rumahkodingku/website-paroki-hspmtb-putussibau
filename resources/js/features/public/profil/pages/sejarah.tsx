import { Head } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { HistoryTimeline } from '../components/history-timeline';
import type { PublicHistoryItem } from '../types';
import { ProfileHero } from '../components/profile-hero';

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
                <ProfileHero
                    ariaLabelledBy="history-title"
                    title="Sejarah Paroki"
                    description={
                        <Text as="p" variant="body" className="mt-5 max-w-lg">
                            Mengenal perjalanan iman umat dan perkembangan
                            pelayanan paroki dari masa ke masa.
                        </Text>
                    }
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
