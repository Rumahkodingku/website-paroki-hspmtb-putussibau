import { CalendarDays, Clock3, Info, MapPin, Tag } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { formatWibDate, formatWibClock } from '@/lib/datetime';
import type { PublicEventDetail } from '../types';

export function EventInfoList({
    event,
    displayTimezone,
}: {
    event: PublicEventDetail;
    displayTimezone: string;
}) {
    return (
        <dl className="space-y-5">
            <div className="flex items-start gap-3">
                <CalendarDays
                    className="mt-0.5 size-5 shrink-0 text-primary"
                    aria-hidden="true"
                />
                <div className="min-w-0 space-y-1">
                    <dt>
                        <Text as="span" variant="label" color="muted">
                            Tanggal
                        </Text>
                    </dt>
                    <dd>
                        <Text as="p" variant="body" weight="semibold">
                            {formatWibDate(event.start_at, displayTimezone)}
                        </Text>
                        {event.is_all_day ? (
                            <Text as="p" variant="body-sm" color="muted">
                                Sepanjang hari
                            </Text>
                        ) : null}
                    </dd>
                </div>
            </div>

            {!event.is_all_day ? (
                <div className="flex items-start gap-3">
                    <Clock3
                        className="mt-0.5 size-5 shrink-0 text-primary"
                        aria-hidden="true"
                    />
                    <div className="min-w-0 space-y-1">
                        <dt>
                            <Text as="span" variant="label" color="muted">
                                Waktu
                            </Text>
                        </dt>
                        <dd className="mt-2 grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                            <div className="min-w-0">
                                <Text
                                    as="p"
                                    variant="fine-print"
                                    color="muted"
                                    weight="medium"
                                >
                                    Waktu mulai
                                </Text>

                                <Text
                                    as="p"
                                    variant="body"
                                    weight="semibold"
                                    className="mt-1"
                                >
                                    {formatWibClock(
                                        event.start_at,
                                        displayTimezone,
                                    )}
                                </Text>
                            </div>

                            <div className="min-w-0">
                                <Text
                                    as="p"
                                    variant="fine-print"
                                    color="muted"
                                    weight="semibold"
                                >
                                    Waktu selesai
                                </Text>

                                {event.end_at &&
                                new Date(event.end_at).getTime() >
                                    new Date(event.start_at).getTime() ? (
                                    <Text
                                        as="p"
                                        variant="body"
                                        weight="semibold"
                                        className="mt-1"
                                    >
                                        {formatWibClock(
                                            event.end_at,
                                            displayTimezone,
                                        )}
                                    </Text>
                                ) : (
                                    <Text
                                        as="p"
                                        variant="body"

                                        weight="semibold"
                                        className="mt-1"
                                    >
                                        Belum ditentukan
                                    </Text>
                                )}
                            </div>
                        </dd>
                    </div>
                </div>
            ) : null}

            {event.location_name ? (
                <div className="flex items-start gap-3">
                    <MapPin
                        className="mt-0.5 size-5 shrink-0 text-primary"
                        aria-hidden="true"
                    />
                    <div className="min-w-0 space-y-1">
                        <dt>
                            <Text as="span" variant="label" color="muted">
                                Lokasi
                            </Text>
                        </dt>
                        <dd>
                            {event.location_url ? (
                                <a
                                    href={event.location_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="rounded-sm text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <Text
                                        as="p"
                                        variant="body"
                                        weight="semibold"
                                    >
                                        {event.location_name}
                                    </Text>
                                </a>
                            ) : (
                                <Text as="p" variant="body" weight="semibold">
                                    {event.location_name}
                                </Text>
                            )}
                        </dd>
                    </div>
                </div>
            ) : null}

            {event.category ? (
                <div className="flex items-start gap-3">
                    <Tag
                        className="mt-0.5 size-5 shrink-0 text-primary"
                        aria-hidden="true"
                    />
                    <div className="min-w-0 space-y-1">
                        <dt>
                            <Text as="span" variant="label" color="muted">
                                Kategori
                            </Text>
                        </dt>
                        <dd>
                            <Badge
                                variant="outline"
                                className="border-primary/30 bg-primary/5 text-primary-on-surface"
                            >
                                {event.category.name}
                            </Badge>
                        </dd>
                    </div>
                </div>
            ) : null}

            <div className="flex items-start gap-3">
                <Info
                    className="mt-0.5 size-5 shrink-0 text-primary"
                    aria-hidden="true"
                />
                <div className="min-w-0 space-y-1">
                    <dt>
                        <Text as="span" variant="label" color="muted">
                            Status
                        </Text>
                    </dt>
                    <dd>
                        <Badge
                            variant={
                                event.status === 'cancelled'
                                    ? 'destructive'
                                    : 'secondary'
                            }
                        >
                            {event.status_label}
                        </Badge>
                    </dd>
                </div>
            </div>
        </dl>
    );
}
