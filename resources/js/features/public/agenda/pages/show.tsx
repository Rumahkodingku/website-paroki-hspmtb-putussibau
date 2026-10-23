import { Head, usePage } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import type { SeoDefaults } from '@/types';
import { PublicSection } from '@/components/public-section';
import { RichText } from '@/components/rich-text';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { home } from '@/routes';
import { index as agendaIndex } from '@/routes/agenda';
import type { AgendaShowProps } from '../types';
import { EventJsonLd } from '../components/event-json-ld';
import { EventInfoList } from '../components/event-info-list';
import { EventCalendarActions } from '../components/event-calendar-actions';

export default function Show({ event }: AgendaShowProps) {
    const { displayTimezone } = usePage().props;
    const { seo } = usePage<{ seo: SeoDefaults }>().props;
    const url = typeof window === 'undefined' ? '' : window.location.href;

    const hasDescription = Boolean(event.description?.trim());

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

                <article className="mx-auto max-w-7xl">
                    <div className="grid items-start gap-8 lg:grid-cols-[minmax(0,1.45fr)_minmax(19rem,0.85fr)] lg:gap-10">
                        <div className="min-w-0 space-y-8">
                            {event.poster ? (
                                <figure className="overflow-hidden rounded-2xl">
                                    <img
                                        src={event.poster}
                                        alt={event.poster_alt ?? ''}
                                        className="h-auto max-h-[min(68vh,52rem)] w-full object-contain"
                                        fetchPriority="high"
                                    />
                                </figure>
                            ) : (
                                <div className="flex aspect-video items-center justify-center rounded-xl border border-dashed border-border bg-muted/20">
                                    <div className="space-y-2 text-center">
                                        <CalendarDays
                                            className="mx-auto size-8 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                        >
                                            Poster kegiatan belum tersedia.
                                        </Text>
                                    </div>
                                </div>
                            )}

                            {hasDescription ? (
                                <section
                                    aria-labelledby="tentang-kegiatan"
                                    className="space-y-4"
                                >
                                    <div className="space-y-2">
                                        <Text
                                            as="h2"
                                            variant="h3"
                                            id="tentang-kegiatan"
                                        >
                                            Tentang Kegiatan
                                        </Text>
                                    </div>
                                    <RichText
                                        html={event.description!}
                                        className="max-w-none"
                                    />
                                </section>
                            ) : null}
                        </div>

                        <aside className="min-w-0 lg:sticky lg:top-24">
                            <section aria-labelledby="informasi-kegiatan">
                                <Text
                                    as="h2"
                                    variant="h4"
                                    id="informasi-kegiatan"
                                    className="mb-5"
                                >
                                    Informasi Kegiatan
                                </Text>

                                <EventInfoList
                                    event={event}
                                    displayTimezone={displayTimezone}
                                />

                                <EventCalendarActions
                                    event={event}
                                    displayTimezone={displayTimezone}
                                    url={url}
                                />
                            </section>
                        </aside>
                    </div>
                </article>
            </PublicSection>
        </>
    );
}
