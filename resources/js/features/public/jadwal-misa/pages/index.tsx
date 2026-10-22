import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { PublicSection } from '@/components/public-section';
import PublicHeroBanner from '@/components/public-hero-banner';
import { EmptyState } from '@/components/empty-state';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { DAY_LABELS } from '../components/regular-mass-schedule-card';
import { RegularMassScheduleCard } from '../components/regular-mass-schedule-card';
import { NextMassSection } from '../components/next-mass-section';
import { ScheduleFilter } from '../components/schedule-filter';
import { ScheduleNoticeBanner } from '../components/schedule-notice-banner';
import type {
    NextMass as NextMassData,
    PublicLocation,
    PublicMassSchedule,
    PublicScheduleNotice,
    PublicSpecialMass,
    ScheduleFilters,
} from '../types';
import { CalendarDays, ChevronDown } from 'lucide-react';
import { SpecialMassScheduleCard } from '../components/special-mass-schedule-card';
import { home } from '@/routes';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';

type Props = {
    nextMass: NextMassData | null;
    regularMasses: PublicMassSchedule[];
    specialMasses: PublicSpecialMass[];
    notices: PublicScheduleNotice[];
    locations: PublicLocation[];
    filters: ScheduleFilters;
};

export default function JadwalMisa({
    nextMass,
    regularMasses,
    specialMasses,
    notices,
    locations,
    filters,
}: Props) {
    const { displayTimezone } = usePage().props;
    const [tab, setTab] = useState<'rutin' | 'khusus'>('rutin');

    const applyFilter = (next: {
        location_id: number | null;
        day: number | null;
    }) => {
        router.get(
            jadwalMisa().url,
            {
                lokasi: next.location_id ?? undefined,
                hari: next.day ?? undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const regularMassGroups = Object.entries(DAY_LABELS)
        .map(([day, label]) => ({
            day: Number(day),
            label,
            masses: regularMasses.filter(
                (mass) => mass.day_of_week === Number(day),
            ),
        }))
        .filter((group) => group.masses.length > 0);

    return (
        <>
            <Seo
                title="Jadwal Misa"
                description="Jadwal misa rutin, misa khusus, dan pemberitahuan perubahan jadwal di Paroki HSPMTB Putussibau."
            />

            <PublicHeroBanner
                eyebrow="Ibadah"
                title="Jadwal Misa"
                description="Jadwal misa rutin setiap minggu, misa khusus pada tanggal tertentu, dan pemberitahuan perubahan jadwal."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Jadwal Misa' },
                ]}
            />

            <NextMassSection
                nextMass={nextMass}
                displayTimezone={displayTimezone}
            />

            <PublicSection>
                <div className="space-y-10">
                    <ScheduleNoticeBanner notices={notices} />

                    <ScheduleFilter
                        locations={locations}
                        dayLabels={DAY_LABELS}
                        locationId={filters.location_id}
                        day={filters.day}
                        onChange={applyFilter}
                    />

                    <Tabs
                        value={tab}
                        onValueChange={(value) =>
                            setTab(value as 'rutin' | 'khusus')
                        }
                    >
                        <TabsList variant="line">
                            <TabsTrigger value="rutin">Misa Rutin</TabsTrigger>
                            <TabsTrigger value="khusus">
                                Misa Khusus
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="rutin" className="pt-6">
                            {regularMasses.length === 0 ? (
                                <EmptyState
                                    title={
                                        filters.location_id !== null ||
                                        filters.day !== null
                                            ? 'Tidak ada jadwal untuk filter ini.'
                                            : 'Belum ada jadwal misa rutin.'
                                    }
                                    description={
                                        filters.location_id !== null ||
                                        filters.day !== null
                                            ? 'Coba reset filter untuk melihat seluruh jadwal.'
                                            : 'Jadwal misa rutin belum dipublikasikan.'
                                    }
                                />
                            ) : (
                                <div className="space-y-3">
                                    <div className="mb-10 space-y-1">
                                        <Text as="h2" variant="heading-sm">
                                            Jadwal Misa Rutin
                                        </Text>

                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                        >
                                            Daftar misa rutin yang ada di paroki
                                        </Text>
                                    </div>

                                    {regularMassGroups.map((group) => (
                                        <div
                                            key={group.day}
                                            className="mb-4 overflow-hidden border-b border-border/70 pb-4"
                                        >
                                            {/* Mobile: collapsible */}
                                            <div className="sm:hidden">
                                                <Collapsible
                                                    defaultOpen={
                                                        group.day === 0
                                                    }
                                                >
                                                    <CollapsibleTrigger asChild>
                                                        <button
                                                            type="button"
                                                            className="flex w-full items-center gap-3 rounded-xl p-4 text-left transition-colors hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                        >
                                                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                                                <CalendarDays
                                                                    className="size-5"
                                                                    aria-hidden="true"
                                                                />
                                                            </div>

                                                            <div className="min-w-0 flex-1">
                                                                <Text
                                                                    as="h3"
                                                                    variant="heading-sm"
                                                                >
                                                                    {
                                                                        group.label
                                                                    }
                                                                </Text>
                                                                <Text
                                                                    as="p"
                                                                    variant="body-sm"
                                                                    color="muted"
                                                                >
                                                                    {
                                                                        group
                                                                            .masses
                                                                            .length
                                                                    }{' '}
                                                                    misa
                                                                </Text>
                                                            </div>

                                                            <ChevronDown
                                                                className="size-4 shrink-0 text-muted-foreground transition-transform duration-200"
                                                                aria-hidden="true"
                                                            />
                                                        </button>
                                                    </CollapsibleTrigger>

                                                    <CollapsibleContent>
                                                        <ul className="divide-y divide-border/70 px-4 py-3">
                                                            {group.masses.map(
                                                                (mass) => (
                                                                    <RegularMassScheduleCard
                                                                        key={
                                                                            mass.id
                                                                        }
                                                                        mass={
                                                                            mass
                                                                        }
                                                                    />
                                                                ),
                                                            )}
                                                        </ul>
                                                    </CollapsibleContent>
                                                </Collapsible>
                                            </div>

                                            {/* Desktop: always visible, without collapsible */}
                                            <div className="hidden sm:grid sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-10">
                                                <div className="flex items-center gap-3 p-4 sm:px-5">
                                                    <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                                        <CalendarDays
                                                            className="size-5"
                                                            aria-hidden="true"
                                                        />
                                                    </div>

                                                    <div className="min-w-0">
                                                        <Text
                                                            as="h3"
                                                            variant="heading-sm"
                                                        >
                                                            {group.label}
                                                        </Text>
                                                        <Text
                                                            as="p"
                                                            variant="body-sm"
                                                            color="muted"
                                                        >
                                                            {
                                                                group.masses
                                                                    .length
                                                            }{' '}
                                                            misa
                                                        </Text>
                                                    </div>
                                                </div>

                                                <ul className="min-w-0 divide-y divide-border/70 px-4 py-3 sm:px-5">
                                                    {group.masses.map(
                                                        (mass) => (
                                                            <RegularMassScheduleCard
                                                                key={mass.id}
                                                                mass={mass}
                                                            />
                                                        ),
                                                    )}
                                                </ul>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </TabsContent>

                        <TabsContent value="khusus" className="pt-6">
                            {specialMasses.length === 0 ? (
                                <EmptyState
                                    title="Belum ada misa khusus mendatang."
                                    description="Misa khusus akan tampil di sini setelah dipublikasikan."
                                />
                            ) : (
                                <div className="space-y-5">
                                    <div className="space-y-1">
                                        <Text as="h2" variant="heading-sm">
                                            Jadwal Misa Khusus
                                        </Text>

                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                        >
                                            Daftar misa khusus dan perayaan
                                            tertentu di paroki, stasi, dan
                                            kapel.
                                        </Text>
                                    </div>

                                    <ul className="space-y-3">
                                        {specialMasses.map((mass) => (
                                            <SpecialMassScheduleCard
                                                key={mass.id}
                                                mass={mass}
                                                displayTimezone={
                                                    displayTimezone
                                                }
                                            />
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </TabsContent>
                    </Tabs>
                </div>
            </PublicSection>
        </>
    );
}
