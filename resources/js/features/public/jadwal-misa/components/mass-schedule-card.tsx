import { Text } from '@/components/text';
import { formatWibTime } from '@/lib/datetime';
import type { PublicMassSchedule } from '../types';
import { LocationLink } from './next-mass-card';

/*
 * One row of the weekly schedule — DESIGN.md's MassScheduleCard.
 *
 * DESIGN.md asks for day, mass name, time and location to be visually separate,
 * and allows the next upcoming mass a subtle red accent. The accent is not
 * applied here: choosing which row is "next" would be a second calculation of
 * something NextMassResolver already decided, and it would be a client-side one.
 * The next mass is the card above this list.
 *
 * start_time arrives as "07:30" and is formatted by formatWibTime, a string
 * operation. Handing it to a date formatter is the mistake that turns a 07.30
 * mass into 00.30 (PRD D-02).
 */

type Props = {
    mass: PublicMassSchedule;
    /** Sunday first, matching the order the server already sends them in. */
    dayLabels: Record<number, string>;
};

export function MassScheduleCard({ mass, dayLabels }: Props) {
    return (
        <li
            className="grid gap-3 border-t border-hairline pt-5 sm:grid-cols-[7rem_1fr_auto] sm:gap-4"
            data-test="mass-schedule-card"
        >
            {/* The day is a fixed-width column so the times line up as a column
                instead of a ragged left edge. */}
            <Text as="p" variant="caption-strong" color="brand">
                {dayLabels[mass.day_of_week] ?? ''}
            </Text>

            <div className="space-y-1">
                <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <Text
                        as="p"
                        variant="body-strong"
                        className="text-primary tabular-nums"
                    >
                        {formatWibTime(mass.start_time)}
                    </Text>
                    <Text as="p" variant="body-strong">
                        {mass.name}
                    </Text>
                </div>

                <Text as="p" variant="caption" color="muted">
                    {mass.location.name}
                </Text>

                {mass.notes ? (
                    <Text as="p" variant="caption" color="muted">
                        {mass.notes}
                    </Text>
                ) : null}
            </div>

            <div className="sm:justify-self-end">
                <LocationLink location={mass.location} />
            </div>
        </li>
    );
}

/**
 * Sunday first, matching the order the server already sends them in.
 *
 * Written here rather than fetched as a prop because it is a property of the
 * calendar, not of the parish: no admin can reorder the week, and a locale file
 * would wrongly suggest they could.
 */
export const DAY_LABELS: Record<number, string> = {
    0: 'Minggu',
    1: 'Senin',
    2: 'Selasa',
    3: 'Rabu',
    4: 'Kamis',
    5: 'Jumat',
    6: 'Sabtu',
};
