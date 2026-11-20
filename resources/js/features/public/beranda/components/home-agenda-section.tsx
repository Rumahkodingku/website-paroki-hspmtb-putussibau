import { Link } from '@inertiajs/react';
import Image from '@/components/image';
import { ArrowRight, CalendarDays, Clock3, MapPin } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { index as agenda } from '@/routes/agenda';
import { cn } from '@/lib/utils';
import { formatWibClockValue, formatWibDayParts } from '@/lib/datetime';
import type { EventCardData } from '@/components/event-card';

type HomeAgendaSectionProps = {
    events: EventCardData[];
    displayTimezone: string;
};

function formatEventTime(
    startAt: string,
    endAt: string | null | undefined,
    isAllDay: boolean,
    timezone: string,
) {
    if (isAllDay) {
        return 'Sepanjang hari';
    }

    const startTime = formatWibClockValue(startAt, timezone);

    if (startTime === '') {
        return 'Waktu belum tersedia';
    }

    if (!endAt) {
        return `${startTime} WIB`;
    }

    const endTime = formatWibClockValue(endAt, timezone);

    if (
        endTime === '' ||
        new Date(endAt).getTime() <= new Date(startAt).getTime()
    ) {
        return `${startTime} WIB`;
    }

    return `${startTime}–${endTime} WIB`;
}

export function HomeAgendaSection({
    events,
    displayTimezone,
}: HomeAgendaSectionProps) {
    const [selectedCategory] = useState('all');

    const filteredEvents = useMemo(() => {
        if (selectedCategory === 'all') {
            return events;
        }

        return events.filter(
            (event) => event.category?.slug === selectedCategory,
        );
    }, [events, selectedCategory]);

    if (events.length === 0) {
        return null;
    }

    return (
        <PublicSection surface="default">
            <div className="space-y-7 sm:space-y-8">
                <SectionHeading
                    eyebrow="Kegiatan Umat"
                    title="Agenda Mendatang"
                    description="Kegiatan yang akan datang, terdekat lebih dahulu."
                    action={
                        <Button
                            asChild
                            variant="outline"
                            className="h-11 rounded-full border-primary/70 bg-transparent px-5 text-sm text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
                        >
                            <Link href={agenda()}>Semua Agenda</Link>
                        </Button>
                    }
                />

                {filteredEvents.length > 0 ? (
                    <div className="grid gap-x-4 gap-y-4 sm:gap-x-5 sm:gap-y-5 lg:grid-cols-2">
                        {filteredEvents.map((event) => {
                            const dateParts = Number.isNaN(
                                new Date(event.start_at).getTime(),
                            )
                                ? null
                                : formatWibDayParts(
                                      event.start_at,
                                      displayTimezone,
                                  );

                            const time = formatEventTime(
                                event.start_at,
                                event.end_at,
                                event.is_all_day,
                                displayTimezone,
                            );

                            const cancelled = event.status === 'cancelled';
                            const postponed = event.status === 'postponed';

                            return (
                                <article
                                    key={event.id}
                                    className="group flex min-w-0 gap-3 pb-5 sm:gap-4 sm:p-4"
                                >
                                    {event.poster ? (
                                        <Link
                                            href={`/agenda/${event.slug}`}
                                            tabIndex={-1}
                                            aria-hidden="true"
                                            className="relative aspect-3/4 w-19 shrink-0 self-start overflow-hidden rounded-lg bg-muted sm:w-25"
                                        >
                                            <Image
                                                src={event.poster}
                                                alt=""
                                                loading="lazy"
                                                decoding="async"
                                                fit="cover"
                                                imageClassName="transition-transform duration-300 group-hover:scale-105"
                                                className="size-full"
                                            />
                                        </Link>
                                    ) : (
                                        <div className="flex aspect-3/4 w-19 shrink-0 items-center justify-center self-start rounded-lg bg-muted text-muted-foreground sm:w-25">
                                            <CalendarDays
                                                className="size-7"
                                                aria-hidden="true"
                                            />
                                        </div>
                                    )}

                                    <div className="flex w-11 shrink-0 flex-col items-center gap-0.5 rounded-lg px-1 py-2 sm:w-13.5">
                                        <Text
                                            variant="label"
                                            color="danger"
                                            transform="uppercase"
                                            className="text-[10px] sm:text-xs"
                                        >
                                            {dateParts?.month ?? '-'}
                                        </Text>

                                        <Text
                                            as="span"
                                            variant="h3"
                                            color="foreground"
                                            className="text-2xl leading-tight sm:text-3xl"
                                        >
                                            {dateParts?.day ?? '-'}
                                        </Text>

                                        <Text
                                            variant="caption"
                                            color="muted"
                                            className="text-[10px] sm:text-xs"
                                        >
                                            {dateParts?.weekday ?? ''}
                                        </Text>
                                    </div>

                                    <div className="flex min-w-0 flex-1 flex-col items-start gap-2">
                                        <div className="flex w-full flex-wrap items-center gap-2">
                                            {event.category && (
                                                <Text
                                                    variant="label"
                                                    className="inline-flex max-w-full items-center gap-1.5 rounded-full px-2.5 py-1"
                                                >
                                                    {event.category.name}
                                                </Text>
                                            )}

                                            {(cancelled || postponed) && (
                                                <Text
                                                    variant="label"
                                                    color="default"
                                                    className={cn(
                                                        'rounded-full px-2.5 py-1',
                                                        cancelled
                                                            ? 'bg-destructive/10 text-destructive'
                                                            : 'bg-warning-soft text-warning',
                                                    )}
                                                >
                                                    {cancelled
                                                        ? 'Dibatalkan'
                                                        : 'Ditunda'}
                                                </Text>
                                            )}
                                        </div>

                                        <Text
                                            as="h3"
                                            variant="h4"
                                            color="foreground"
                                            className="leading-snug tracking-tight"
                                        >
                                            <Link
                                                href={`/agenda/${event.slug}`}
                                                className="rounded-sm transition-colors outline-none hover:text-primary focus-visible:ring-2 focus-visible:ring-ring"
                                            >
                                                {event.title}
                                            </Link>
                                        </Text>

                                        <div className="flex flex-col gap-1.5">
                                            <div className="flex items-start gap-2">
                                                {event.is_all_day ? (
                                                    <CalendarDays
                                                        className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                                        aria-hidden="true"
                                                    />
                                                ) : (
                                                    <Clock3
                                                        className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                                        aria-hidden="true"
                                                    />
                                                )}

                                                <Text
                                                    variant="body-sm"
                                                    color="muted"
                                                >
                                                    {time}
                                                </Text>
                                            </div>

                                            {event.location_name && (
                                                <div className="flex min-w-0 items-start gap-2">
                                                    <MapPin
                                                        className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                                        aria-hidden="true"
                                                    />

                                                    <Text
                                                        variant="body-sm"
                                                        color="muted"
                                                        lineClamp={2}
                                                    >
                                                        {event.location_name}
                                                    </Text>
                                                </div>
                                            )}
                                        </div>

                                        {event.summary && (
                                            <Text
                                                variant="body-sm"
                                                color="muted"
                                                lineClamp={2}
                                                className="leading-relaxed"
                                            >
                                                {event.summary}
                                            </Text>
                                        )}

                                        <Link
                                            href={`/agenda/${event.slug}`}
                                            className="mt-auto inline-flex min-h-9 items-center gap-1.5 rounded-sm text-primary transition-colors hover:text-primary/80 focus-visible:ring-2 focus-visible:ring-ring"
                                        >
                                            <Text
                                                variant="body-sm"
                                                weight="medium"
                                                color="inherit"
                                            >
                                                Lihat detail
                                            </Text>

                                            <ArrowRight
                                                className="size-4 transition-transform group-hover:translate-x-0.5"
                                                aria-hidden="true"
                                            />
                                        </Link>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                ) : (
                    <div className="rounded-xl border border-dashed border-border px-5 py-10 text-center">
                        <CalendarDays
                            className="mx-auto mb-3 size-8 text-muted-foreground"
                            aria-hidden="true"
                        />

                        <Text
                            as="p"
                            variant="body"
                            weight="medium"
                            color="foreground"
                        >
                            Belum ada agenda dalam kategori ini.
                        </Text>

                        <Text variant="body-sm" color="muted" className="mt-1">
                            Silakan pilih kategori lainnya.
                        </Text>
                    </div>
                )}
            </div>
        </PublicSection>
    );
}

export default HomeAgendaSection;
