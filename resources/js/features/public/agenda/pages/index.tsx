import { Head, usePage } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import PublicHeroBanner from '@/components/public-hero-banner';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { wibMonthLabel, wibShiftMonth } from '@/lib/datetime';
import type { AgendaIndexProps } from '../types';
import { AgendaCalendar } from '../components/agenda-calendar';
import { AgendaControls } from '../components/agenda-controls';
import { home } from '@/routes';
import { EventCard } from '../components/event-card';

export default function Index({
    mode,
    upcoming,
    monthly,
    categories,
    today,
    filters,
}: AgendaIndexProps) {
    const { displayTimezone } = usePage().props;

    const monthLabel = wibMonthLabel(filters.bulan);
    const isCalendar = mode === 'kalender';
    const shown = isCalendar ? monthly : upcoming;

    return (
        <>
            <Seo
                title="Agenda Kegiatan"
                description="Agenda synodal, rapat, dan kegiatan lainnya di Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
            />

            <Head title="Agenda Kegiatan" />

            <PublicHeroBanner
                eyebrow="Agenda"
                title="Agenda Kegiatan"
                description="Agenda synodal, rapat, dan kegiatan lainnya di paroki."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Agenda Kegiatan' },
                ]}
            />

            <PublicSection>
                <div className="space-y-10">
                    <AgendaControls
                        mode={mode}
                        month={filters.bulan}
                        categories={categories}
                        today={today}
                        todayMonth={today.slice(0, 7)}
                        previousMonth={wibShiftMonth(filters.bulan, -1)}
                        nextMonth={wibShiftMonth(filters.bulan, 1)}
                        activeCategory={filters.kategori}
                    />

                    {isCalendar ? (
                        <section aria-labelledby="agenda-bulan">
                            <Text
                                as="h2"
                                variant="h3"
                                id="agenda-bulan"
                                className="sr-only"
                            >
                                {monthLabel}
                            </Text>

                            <div className="mt-4">
                                <AgendaCalendar
                                    monthKey={filters.bulan}
                                    today={today}
                                    events={monthly}
                                    displayTimezone={displayTimezone}
                                />
                            </div>
                        </section>
                    ) : (
                        <section aria-labelledby="agenda-mendatang">
                            <Text as="h2" variant="h3" id="agenda-mendatang">
                                Agenda mendatang
                            </Text>

                            {shown.length > 0 ? (
                                <div className="mt-8 space-y-4 sm:mt-10 sm:space-y-5">
                                    {shown.map((event) => (
                                        <EventCard
                                            key={event.id}
                                            event={event}
                                            displayTimezone={displayTimezone}
                                        />
                                    ))}
                                </div>
                            ) : (
                                <Text
                                    as="p"
                                    variant="body"
                                    color="muted"
                                    className="mt-6"
                                >
                                    Belum ada agenda yang diumumkan.
                                </Text>
                            )}
                        </section>
                    )}
                </div>
            </PublicSection>
        </>
    );
}
