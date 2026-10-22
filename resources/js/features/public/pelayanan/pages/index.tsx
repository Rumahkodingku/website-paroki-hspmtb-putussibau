import { Head } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import PublicHeroBanner from '@/components/public-hero-banner';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { PublicServiceCard } from '../components/service-card';
import type { PublicServiceGroup } from '../types';
import { home } from '@/routes';

type Props = {
    groups: PublicServiceGroup[];
    hasServices: boolean;
};

export default function PelayananIndex({ groups, hasServices }: Props) {
    return (
        <>
            <Seo
                title="Pelayanan"
                description="Informasi syarat, prosedur, dan cara mendaftar pelayanan sakramen dan pastoral di Paroki HSPMTB Putussibau."
            />

            <Head title="Pelayanan" />

            <PublicHeroBanner
                eyebrow="Pelayanan"
                title="Pelayanan Sakramen & Pastoral"
                description="Informasi syarat, prosedur, dan cara mendaftar untuk pelayanan sakramen dan pastoral di paroki."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Pelayanan' },
                ]}
            />

            <PublicSection>
                {!hasServices ? (
                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        className="mt-10 rounded-lg border border-dashed border-border p-6"
                    >
                        Belum ada layanan yang dipublikasikan. Silakan kembali
                        lagi nanti.
                    </Text>
                ) : (
                    <div className="mt-12 space-y-14">
                        {groups.map((group) => (
                            <section
                                key={group.id}
                                aria-labelledby={`group-${group.id}`}
                            >
                                <Text
                                    as="h2"
                                    variant="h3"
                                    id={`group-${group.id}`}
                                >
                                    {group.name}
                                </Text>

                                <div className="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                    {group.services.map((service) => (
                                        <PublicServiceCard
                                            key={service.id}
                                            service={service}
                                        />
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>
                )}
            </PublicSection>
        </>
    );
}
