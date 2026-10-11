import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { EmptyState } from '@/components/empty-state';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatWibDateTime } from '@/lib/datetime';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { DAY_LABELS, MassScheduleCard } from '../components/mass-schedule-card';
import { NextMassCard } from '../components/next-mass-card';
import { ScheduleFilter } from '../components/schedule-filter';
import { ScheduleNoticeBanner } from '../components/schedule-notice-banner';
import { LocationInfo, ShareActions } from '../components/share-actions';
import type {
    NextMass as NextMassData,
    PublicLocation,
    PublicMassSchedule,
    PublicScheduleNotice,
    PublicSpecialMass,
    ScheduleFilters,
} from '../types';

/*
 * /jadwal-misa — the page PRD 7.3 calls the most-visited page on the site.
 *
 * Three things arrive already decided and are used as given:
 *
 *   nextMass        NextMassResolver's answer, already in the display timezone.
 *                   Nothing here recomputes it — roadmap 7.2 forbids a second
 *                   schedule calculation in React, and a page that disagreed
 *                   with the resolver would disagree with its own tests.
 *
 *   regularMasses   Already filtered to active schedules at active locations.
 *                   Filtering again here would hide nothing and would break if
 *                   MASS-07's rule were ever relaxed server-side.
 *
 *   specialMasses   Already filtered to what has not happened yet (MASS-02), so
 *                   a past mass cannot appear on the tab even for one frame.
 *
 * The filters are in the URL. That makes a filtered view shareable, printable and
 * reload-safe for free, and it is the mechanism roadmap 7.2 asks for.
 *
 * Which tab is open is component state rather than a query parameter. It is a
 * view preference, not a filter: two links to the same filtered schedule with
 * different tabs open are the same page, and putting it in the URL would make the
 * back button step through tab changes as though they were pages.
 */

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
    const { displayTimezone, seo } = usePage().props;
    const [tab, setTab] = useState<'rutin' | 'khusus'>('rutin');

    // Read from window rather than from a prop: the shareable URL is the one in
    // the address bar, which is the only version that carries the filter.
    const shareUrl =
        typeof window === 'undefined' ? seo.appUrl : window.location.href;

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

    return (
        <>
            {/*
              Seo rather than Inertia's Head, because it is the component that
              keeps the page's meta tags in step with the ones app.blade.php
              renders. SSR is off (D-26), so the blade output is what a crawler
              and a WhatsApp unfurl actually read; Head alone would leave the
              description coming only from site_settings.
            */}
            <Seo
                title="Jadwal Misa"
                description="Jadwal misa rutin, misa khusus, dan pemberitahuan perubahan jadwal di Paroki HSPMTB Putussibau."
            />

            <PublicSection>
                <SectionHeading
                    eyebrow="Ibadah"
                    title="Jadwal Misa"
                    description="Jadwal misa rutin setiap minggu, misa khusus pada tanggal tertentu, dan pemberitahuan perubahan jadwal."
                />

                <div className="space-y-10">
                    <NextMassCard
                        nextMass={nextMass}
                        displayTimezone={displayTimezone}
                    />

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
                        <TabsList>
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
                                <ul>
                                    {regularMasses.map((mass) => (
                                        <MassScheduleCard
                                            key={mass.id}
                                            mass={mass}
                                            dayLabels={DAY_LABELS}
                                        />
                                    ))}
                                </ul>
                            )}
                        </TabsContent>

                        <TabsContent value="khusus" className="pt-6">
                            {specialMasses.length === 0 ? (
                                <EmptyState
                                    title="Belum ada misa khusus mendatang."
                                    description="Misa khusus akan tampil di sini setelah dipublikasikan."
                                />
                            ) : (
                                <ul className="space-y-6">
                                    {specialMasses.map((mass) => (
                                        <li
                                            key={mass.id}
                                            className="space-y-2 border-t border-hairline pt-5"
                                            data-test="special-mass-card"
                                        >
                                            <Text as="p" variant="body-strong">
                                                {mass.title}
                                            </Text>

                                            <Text
                                                as="p"
                                                variant="caption"
                                                color="muted"
                                            >
                                                {formatWibDateTime(
                                                    mass.start_at,
                                                    displayTimezone,
                                                )}
                                                {mass.end_at
                                                    ? ` – ${
                                                          formatWibDateTime(
                                                              mass.end_at,
                                                              displayTimezone,
                                                          ).split(' · ')[1]
                                                      }`
                                                    : ''}
                                            </Text>

                                            <Text
                                                as="p"
                                                variant="caption"
                                                color="muted"
                                            >
                                                {mass.location.name}
                                            </Text>

                                            {mass.description ? (
                                                <Text as="p" variant="body">
                                                    {mass.description}
                                                </Text>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </TabsContent>
                    </Tabs>

                    {locations.length > 0 ? (
                        <section aria-label="Lokasi misa" className="space-y-4">
                            <Text as="h2" variant="h3">
                                Lokasi
                            </Text>

                            <div className="grid gap-6 sm:grid-cols-2">
                                {locations.map((location) => (
                                    <div
                                        key={location.id}
                                        className="rounded-lg border border-hairline p-5"
                                    >
                                        <LocationInfo location={location} />
                                    </div>
                                ))}
                            </div>
                        </section>
                    ) : null}

                    <ShareActions
                        url={shareUrl}
                        title="Jadwal Misa Paroki HSPMTB Putussibau"
                    />
                </div>
            </PublicSection>
        </>
    );
}
