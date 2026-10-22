import { Clock, MapPin } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { LocationLink } from '@/components/next-mass-card';
import { formatWibTime } from '@/lib/datetime';
import type { PublicMassSchedule } from '../types';

type Props = {
    mass: PublicMassSchedule;
};

export function RegularMassScheduleCard({ mass }: Props) {
    return (
        <li
            className="flex min-w-0 flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:grid sm:grid-cols-[5.25rem_minmax(0,1fr)_2rem] sm:items-center sm:gap-8"
            data-test="mass-schedule-card"
        >
            <Badge
                variant="secondary"
                className="flex h-9 w-fit items-center gap-2 rounded-xl border-0 bg-primary/5 px-3 text-xs font-semibold text-primary-on-surface tabular-nums"
            >
                <Clock className="size-4 shrink-0" aria-hidden="true" />
                {formatWibTime(mass.start_time)}
            </Badge>

            <div className="min-w-0 space-y-1">
                <Text
                    as="h4"
                    variant="heading-sm"
                    className="text-sm leading-snug font-semibold sm:text-base"
                >
                    {mass.name}
                </Text>

                <div className="flex min-w-0 items-start gap-1.5 text-muted-foreground">
                    <MapPin
                        className="mt-0.5 size-3.5 shrink-0"
                        aria-hidden="true"
                    />
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="min-w-0 text-xs leading-relaxed wrap-break-word sm:text-sm"
                    >
                        {mass.location.name}
                    </Text>
                </div>

                {mass.notes ? (
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="text-xs leading-relaxed"
                    >
                        {mass.notes}
                    </Text>
                ) : null}
            </div>

            <div className="flex justify-end sm:justify-center">
                <LocationLink location={mass.location} />
            </div>
        </li>
    );
}

export const DAY_LABELS: Record<number, string> = {
    0: 'Minggu',
    1: 'Senin',
    2: 'Selasa',
    3: 'Rabu',
    4: 'Kamis',
    5: 'Jumat',
    6: 'Sabtu',
};
