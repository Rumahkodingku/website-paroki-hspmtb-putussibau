import { Text } from '@/components/text';
import { StatusBadge } from '@/components/list-parts';
import { formatWibTime } from '@/lib/datetime';
import { formatDayName } from '@/lib/datetime';
import type { MassScheduleRow } from '../../../types';

/*
 * The mobile rendering of the recurring-mass list.
 *
 * AC-MASS-5 asks for a list rather than a cropped table on a phone, and ADM-01
 * says the admin has to be able to work on one. So this is not a subset of the
 * table: it is the same rows, laid out in a column, showing the same fields an
 * admin reads there.
 *
 * It deliberately carries no actions. On a 360px screen, a row of buttons
 * competes with the data for the same space, and the table's own action column
 * is reachable by scrolling sideways - which is the honest trade: reading on a
 * phone, editing on a wider screen.
 */

export function MassScheduleCards({
    rows,
    days,
}: {
    rows: MassScheduleRow[];
    days: Array<{ value: number; label: string }>;
}) {
    if (rows.length === 0) {
        return null;
    }

    const dayLabel = (value: number): string =>
        days.find((day) => day.value === value)?.label ?? formatDayName(value);

    return (
        <ul className="space-y-3 md:hidden">
            {rows.map((schedule) => (
                <li
                    key={schedule.id}
                    className="rounded-lg border border-hairline p-4"
                >
                    <div className="flex items-start justify-between gap-3">
                        <div className="space-y-1">
                            <Text as="p" variant="body-strong">
                                {schedule.name}
                            </Text>

                            <Text as="p" variant="caption" color="muted">
                                {dayLabel(schedule.day_of_week)},{' '}
                                {formatWibTime(schedule.start_time)}
                            </Text>

                            <Text as="p" variant="caption" color="muted">
                                {schedule.location.name}
                            </Text>
                        </div>

                        <StatusBadge active={schedule.is_active} />
                    </div>
                </li>
            ))}
        </ul>
    );
}
