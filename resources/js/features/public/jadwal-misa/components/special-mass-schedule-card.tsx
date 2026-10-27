import { ChevronRight, Clock3, MapPin } from 'lucide-react';
import { Text } from '@/components/text';
import { formatWibClock } from '@/lib/datetime';
import type { PublicSpecialMass } from '../types';

type Props = {
    mass: PublicSpecialMass;
    displayTimezone: string;
};

export function SpecialMassScheduleCard({ mass, displayTimezone }: Props) {
    const startDate = new Date(mass.start_at);

    const dateParts = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        timeZone: displayTimezone,
    }).formatToParts(startDate);

    const dayNumber =
        dateParts.find((part) => part.type === 'day')?.value ?? '';
    const month = dateParts.find((part) => part.type === 'month')?.value ?? '';

    const weekday = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        timeZone: displayTimezone,
    }).format(startDate);

    const startTime = formatWibClock(mass.start_at, displayTimezone);

    const endTime = mass.end_at
        ? formatWibClock(mass.end_at, displayTimezone)
        : null;

    return (
        <li className="list-none" data-test="special-mass-card">
            <div className="py-0 transition-all duration-200">
                <div className="pt-6">
                    <div className="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-stretch sm:gap-5">
                        {/* Date block */}
                        <div className="flex w-28 shrink-0 items-center justify-center gap-3 rounded-xl bg-primary/5 p-3 sm:flex-col sm:gap-1 sm:p-4">
                            <div className="flex min-w-0 flex-col items-center gap-3 sm:justify-center sm:gap-1">
                                <Text
                                    as="span"
                                    variant="label"
                                    className="shrink-0 text-xs font-semibold tracking-wide text-primary uppercase sm:text-center"
                                >
                                    {month}
                                </Text>

                                <Text
                                    as="span"
                                    className="text-2xl leading-none font-bold tracking-tight text-primary sm:text-3xl"
                                >
                                    {dayNumber}
                                </Text>

                                <Text
                                    as="span"
                                    variant="body-sm"
                                    className="min-w-0 text-xs font-medium text-primary sm:text-center"
                                >
                                    {weekday}
                                </Text>
                            </div>
                        </div>

                        {/* Event details */}
                        <div className="flex min-w-0 flex-1 flex-col justify-center gap-2">
                            <Text
                                as="h3"
                                variant="heading-sm"
                                className="text-base leading-snug font-semibold sm:text-lg"
                            >
                                {mass.title}
                            </Text>

                            <div className="flex min-w-0 items-start gap-2">
                                <Clock3
                                    className="mt-0.5 size-4 shrink-0 text-primary"
                                    aria-hidden="true"
                                />

                                <Text
                                    as="p"
                                    variant="body-sm"
                                    className="text-sm leading-relaxed"
                                >
                                    {startTime}
                                    {endTime ? ` – ${endTime}` : ''}
                                </Text>
                            </div>

                            <div className="flex min-w-0 items-start gap-2">
                                <MapPin
                                    className="mt-0.5 size-4 shrink-0 text-primary"
                                    aria-hidden="true"
                                />

                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="min-w-0 text-sm leading-relaxed wrap-break-word"
                                >
                                    {mass.location.name}
                                </Text>
                            </div>

                            {mass.description ? (
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="pt-1 text-sm leading-relaxed"
                                >
                                    {mass.description}
                                </Text>
                            ) : null}
                        </div>

                        {/* Decorative direction indicator */}
                        {mass.location.mapsUrl ? (
                            <div className="hidden shrink-0 items-center sm:flex">
                                <a
                                    href={mass.location.mapsUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label={`Lihat lokasi ${mass.location.name} di Google Maps`}
                                    title="Lihat lokasi di Google Maps"
                                    className="flex size-9 items-center justify-center rounded-full border border-border text-muted-foreground transition-colors hover:border-primary/30 hover:text-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <ChevronRight
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </a>
                            </div>
                        ) : null}
                    </div>
                </div>
            </div>
        </li>
    );
}
