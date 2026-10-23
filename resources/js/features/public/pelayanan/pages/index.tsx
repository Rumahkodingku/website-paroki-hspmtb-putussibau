import { Head } from '@inertiajs/react';
import { Church, Cross, HeartHandshake, HeartPulse } from 'lucide-react';

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

function getCategoryPresentation(slug: string) {
    switch (slug) {
        case 'inisiasi':
            return {
                description:
                    'Sakramen-sakramen yang menjadi dasar kehidupan umat Katolik.',
                Icon: Cross,
                iconClassName:
                    'bg-red-50 text-primary-on-surface dark:bg-red-950/40 dark:text-red-300',
                numberClassName: 'text-primary-on-surface',
            };

        case 'penyembuhan':
        case 'orang-sakit':
            return {
                description:
                    'Sakramen yang membawa pengampunan, penguatan, dan pendampingan pastoral bagi umat.',
                Icon: HeartPulse,
                iconClassName:
                    'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
                numberClassName: 'text-blue-700 dark:text-blue-300',
            };

        case 'pelayanan-persekutuan':
        case 'persekutuan':
        case 'perkawinan':
            return {
                description:
                    'Sakramen yang meneguhkan panggilan untuk melayani dan membangun persekutuan dalam Gereja.',
                Icon: HeartHandshake,
                iconClassName:
                    'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300',
                numberClassName: 'text-violet-700 dark:text-violet-300',
            };

        default:
            return {
                description:
                    'Temukan informasi dan persyaratan pelayanan paroki.',
                Icon: Church,
                iconClassName: 'bg-muted text-foreground',
                numberClassName: 'text-muted-foreground',
            };
    }
}

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
                            const presentation = getCategoryPresentation(
                                group.slug,
                            );

                            const { Icon } = presentation;

                            return (
                                <section
                                    key={group.id}
                                    aria-labelledby={`group-${group.id}`}
                                    className="scroll-mt-24"
                                >
                                    {/* Category heading */}
                                    <div className="flex items-start gap-4 sm:gap-5">
                                        <div
                                            className={[
                                                'flex size-11 shrink-0 items-center justify-center',
                                                'rounded-xl sm:size-14 sm:rounded-2xl',
                                                presentation.iconClassName,
                                            ].join(' ')}
                                        >
                                            <Icon
                                                className="size-5 sm:size-6"
                                                aria-hidden="true"
                                            />
                                        </div>

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
                                                {presentation.description}
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
                                        {group.services.map(
                                            (service, serviceIndex) => (
                                                <PublicServiceCard
                                                    key={service.id}
                                                    service={service}
                                                    index={serviceIndex}
                                                />
                                            ),
                                        )}
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
