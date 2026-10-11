import { Head, usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import type { SeoDefaults } from '@/types';
import { PublicSection } from '@/components/public-section';
import { RichText } from '@/components/rich-text';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import {
    formatWibDate,
    formatWibTime,
    googleCalendarUrl,
} from '@/lib/datetime';
import { home } from '@/routes';
import { ics as eventIcs, index as agendaIndex } from '@/routes/agenda';
import type { AgendaShowProps } from '../types';
import { EventCard } from '../components/event-card';
import { EventJsonLd } from '../components/event-json-ld';

/*
 * The activity page.
 *
 * The two things this page exists for are at the top, not at the bottom: the
 * date and place, and the two ways to put it in a calendar. Everything else —
 * the description, the poster, the other activities in the same category — is
 * below the answer.
 *
 * AC-EVT-3 is about those two buttons working, so both are built from the UTC
 * instants the server resolved rather than from anything re-formatted in the
 * browser. A hand-built Google URL gets the encoding of a title with an
 * ampersand in it wrong without complaining.
 */
export default function Show({ event, related }: AgendaShowProps) {
    const { displayTimezone } = usePage().props;
    // siteName lives under `seo`, not at the top level — SeoDefaults is where
    // HandleInertiaRequests puts it.
    const { seo } = usePage<{ seo: SeoDefaults }>().props;
    const url = typeof window === 'undefined' ? '' : window.location.href;

    const isNotHappening = event.status !== 'scheduled';

    return (
        <>
            <Seo
                title={`${event.title} — Agenda`}
                description={event.plain_description ?? undefined}
                ogType="article"
                ogImage={event.poster}
                canonical={url || undefined}
            />

            <Head title={event.title} />

            <EventJsonLd event={event} url={url} parishName={seo.siteName} />

            <PublicSection>
                <Breadcrumbs
                    className="mb-8"
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Agenda', href: agendaIndex() },
                        { title: event.title },
                    ]}
                />

                <div className="mx-auto max-w-3xl space-y-8">
                    <article className="space-y-6">
                        <header className="space-y-4">
                            {event.category ? (
                                <Text as="p" variant="tagline" color="brand">
                                    {event.category.name}
                                </Text>
                            ) : null}

                            <Text as="h1" variant="h1">
                                {event.title}
                            </Text>

                            {/*
                             * The cancellation banner is the reason this page
                             * opens on its status. EVT-08 keeps a cancelled
                             * activity reachable; somebody who followed a link
                             * from last week's announcement needs to be told
                             * before they set off, not after reading the
                             * description.
                             */}
                            {isNotHappening ? (
                                <div
                                    role="status"
                                    className="flex items-start gap-3 border-l-4 border-destructive bg-destructive/5 p-4"
                                >
                                    <Badge variant="destructive">
                                        {event.status_label}
                                    </Badge>
                                    <Text as="p" variant="body">
                                        {event.status === 'cancelled'
                                            ? 'Kegiatan ini dibatalkan.'
                                            : 'Waktu kegiatan ini ditunda dan akan diumumkan kembali.'}
                                    </Text>
                                </div>
                            ) : null}

                            <dl className="grid gap-3 border-y border-hairline py-4">
                                <div className="flex flex-wrap items-baseline gap-x-3">
                                    <dt className="w-28 text-micro-legal text-muted-foreground uppercase">
                                        Waktu
                                    </dt>
                                    <dd>
                                        <Text as="p" variant="body-strong">
                                            {formatWibDate(
                                                event.start_at,
                                                displayTimezone,
                                            )}
                                            {event.is_all_day ? (
                                                <span className="ml-2 font-normal text-muted-foreground">
                                                    Sepanjang hari
                                                </span>
                                            ) : (
                                                <span className="ml-2 font-normal text-muted-foreground">
                                                    {formatWibTime(
                                                        event.start_at,
                                                    )}
                                                    {event.end_at
                                                        ? ` – ${formatWibTime(event.end_at)}`
                                                        : ''}
                                                </span>
                                            )}
                                        </Text>
                                    </dd>
                                </div>

                                {event.location_name ? (
                                    <div className="flex flex-wrap items-baseline gap-x-3">
                                        <dt className="w-28 text-micro-legal text-muted-foreground uppercase">
                                            Lokasi
                                        </dt>
                                        <dd>
                                            {event.location_url ? (
                                                <a
                                                    href={event.location_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="rounded-sm text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                                                >
                                                    {event.location_name}
                                                </a>
                                            ) : (
                                                <Text
                                                    as="p"
                                                    variant="body-strong"
                                                >
                                                    {event.location_name}
                                                </Text>
                                            )}
                                        </dd>
                                    </div>
                                ) : null}
                            </dl>
                        </header>

                        {/*
                         * Both calendar actions together, because they are the
                         * same request answered by two programmes, and a reader
                         * who wants it in their calendar does not know which one
                         * they have.
                         */}
                        <div className="flex flex-wrap gap-3">
                            <a
                                href={googleCalendarUrl({
                                    title: event.title,
                                    start: event.start_at,
                                    end: event.end_at,
                                    details: event.plain_description,
                                    location: event.location_name,
                                    isAllDay: event.is_all_day,
                                    timeZone: displayTimezone,
                                })}
                                target="_blank"
                                rel="noopener noreferrer"
                                data-test="add-to-google"
                                className="inline-flex h-11 items-center rounded-md bg-primary px-4 text-sm text-primary-foreground hover:opacity-90 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                            >
                                Tambahkan ke Google Calendar
                            </a>

                            {/*
                             * No download for a cancelled activity. The .ics
                             * would add a thing that is not happening to
                             * somebody's phone, and the file is generated by the
                             * same endpoint that answers 404 for unpublished
                             * activities — but a cancelled one *is* published,
                             * so the refusal has to be here.
                             */}
                            {event.status !== 'cancelled' ? (
                                <a
                                    href={eventIcs(event.slug).url}
                                    data-test="download-ics"
                                    className="inline-flex h-11 items-center rounded-md border border-hairline px-4 text-sm hover:border-primary focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
                                >
                                    Unduh kalender (.ics)
                                </a>
                            ) : null}
                        </div>

                        {event.poster ? (
                            <img
                                src={event.poster}
                                alt={event.poster_alt ?? ''}
                                className="w-full rounded-md object-cover"
                            />
                        ) : null}

                        {/*
                         * The description is stored sanitized and this is the
                         * only place on the agenda that renders it as markup.
                         */}
                        {event.description ? (
                            <RichText html={event.description} />
                        ) : null}
                    </article>
                </div>

                {related.length > 0 ? (
                    <section
                        aria-labelledby="agenda-terkait"
                        className="mx-auto mt-16 max-w-3xl space-y-6"
                    >
                        <Text as="h2" variant="h3" id="agenda-terkait">
                            Kegiatan lain
                        </Text>

                        <div className="grid gap-8 sm:grid-cols-2">
                            {related.map((item) => (
                                <EventCard
                                    key={item.id}
                                    event={item}
                                    displayTimezone={displayTimezone}
                                />
                            ))}
                        </div>
                    </section>
                ) : null}
            </PublicSection>
        </>
    );
}
