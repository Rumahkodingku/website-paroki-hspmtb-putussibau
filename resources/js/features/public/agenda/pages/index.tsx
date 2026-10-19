import { Head, usePage } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { wibMonthLabel, wibShiftMonth } from '@/lib/datetime';
import type { AgendaIndexProps } from '../types';
import { AgendaCalendar } from '../components/agenda-calendar';
import { AgendaControls } from '../components/agenda-controls';
import { EventCard } from '@/components/event-card';

/*
 * The public agenda.
 *
 * Two readings of the same data, and the list is the default. A calendar is
 * the better answer to "what is on the 14th" and the worse answer to "what is
 * coming up" — a grid answers the second question only by making the reader
 * read it cell by cell. PRD EVT-10 asks for both, so both are here, and the
 * list is what a visitor lands on.
 *
 * The server sends both sets in one payload, so switching modes is a URL change
 * that re-renders without a round trip to discover the month has activities.
 */
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

            <PublicSection>
                <div className="space-y-10">
                    <SectionHeading
                        eyebrow="Agenda"
                        title="Agenda Kegiatan"
                        description="Agenda synodal, rapat, dan kegiatan lainnya di paroki."
                    />

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
                            <Text as="h2" variant="h3" id="agenda-bulan">
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

                            {/*
                             * The list under the grid is not a duplicate. It is
                             * the only place the month reads as a whole: a grid
                             * hides a title behind a two-character cell, and the
                             * reader who wants to know what is on the 14th
                             * still has to read the 14th.
                             */}
                            {monthly.length > 0 ? (
                                <div className="mt-10 grid gap-8 sm:grid-cols-2">
                                    {monthly.map((event) => (
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
                                    Belum ada agenda pada bulan ini.
                                </Text>
                            )}
                        </section>
                    ) : (
                        <section aria-labelledby="agenda-mendatang">
                            <Text as="h2" variant="h3" id="agenda-mendatang">
                                Agenda mendatang
                            </Text>

                            {shown.length > 0 ? (
                                <div className="mt-6 grid gap-8 sm:grid-cols-2">
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
