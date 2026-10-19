import { Head } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { RichText } from '@/components/rich-text';
import { Text } from '@/components/text';
import { ProfileSubNav } from '../components/profile-subnav';
import { HistoryTimeline } from '../components/history-timeline';
import type { PublicHistoryItem } from '../types';

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

            <ProfileSubNav />

            <PublicSection>
                <div className="space-y-10">
                    <SectionHeading
                        eyebrow="Profil"
                        title="Sejarah Paroki"
                        description="Kronologi berdirinya dan perjalanan paroki."
                    />

                    {narrative ? (
                        <RichText html={narrative} className="max-w-3xl" />
                    ) : null}

                    {items.length === 0 ? (
                        <Text
                            as="p"
                            variant="body"
                            color="muted"
                            className="rounded-lg border border-dashed border-border p-6"
                        >
                            Linimasa sejarah belum dipublikasikan.
                        </Text>
                    ) : (
                        <HistoryTimeline items={items} />
                    )}
                </div>
            </PublicSection>
        </>
    );
}
