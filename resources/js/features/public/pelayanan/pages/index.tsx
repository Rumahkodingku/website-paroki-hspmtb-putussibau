import { Head } from '@inertiajs/react';
import { home } from '@/routes';
import { PublicSection } from '@/components/public-section';
import PublicHeroBanner from '@/components/public-hero-banner';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { PublicServiceCard } from '../components/service-card';
import type { PublicServiceGroup } from '../types';

type Props = {
    groups: PublicServiceGroup[];
    hasServices: boolean;
};

export default function PelayananIndex({ groups, hasServices }: Props) {
    const visibleGroups = groups.filter((group) => group.services.length > 0);

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
                {!hasServices || visibleGroups.length === 0 ? (
                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        className="mt-10 rounded-xl border border-dashed border-border p-6 sm:p-8"
                    >
                        Belum ada layanan yang dipublikasikan. Silakan kembali
                        lagi nanti.
                    </Text>
                ) : (
                    <div className="mt-8 space-y-12 sm:mt-12 sm:space-y-16 lg:space-y-20">
                        {visibleGroups.map((group, groupIndex) => {
                            return (
                                <section
                                    key={group.id}
                                    aria-labelledby={`group-${group.id}`}
                                    className="scroll-mt-24"
                                >
                                    {/* Category heading */}
                                    <div className="flex items-start gap-4 sm:gap-5">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
                                                <Text
                                                    as="h2"
                                                    variant="h3"
                                                    id={`group-${group.id}`}
                                                    className="text-lg leading-tight font-semibold tracking-tight sm:text-2xl"
                                                >
                                                    {group.name}
                                                </Text>

                                                <Badge
                                                    variant="secondary"
                                                    className="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                >
                                                    {group.services.length}{' '}
                                                    {group.services.length === 1
                                                        ? 'layanan'
                                                        : 'layanan'}
                                                </Badge>
                                            </div>

                                            <Text
                                                as="p"
                                                variant="body"
                                                color="muted"
                                                className="mt-2 max-w-2xl text-sm leading-relaxed sm:mt-2.5 sm:text-base"
                                            >
                                                {group.description}
                                            </Text>
                                        </div>
                                    </div>

                                    {/* Editorial service list */}
                                    <div
                                        className={[
                                            'mt-5 border-t border-border/70',
                                            'sm:mt-7',
                                            groupIndex > 0 ? '' : '',
                                        ].join(' ')}
                                    >
                                        {group.services.map((service) => (
                                            <PublicServiceCard
                                                key={service.id}
                                                service={service}
                                            />
                                        ))}
                                    </div>
                                </section>
                            );
                        })}
                    </div>
                )}
            </PublicSection>
        </>
    );
}
