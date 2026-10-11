import { Text } from '@/components/text';
import { formatWibDateTime } from '@/lib/datetime';
import { StatusBadge } from '@/components/list-parts';
import type { SpecialMassRow } from '../../../types';

/*
 * The mobile rendering of the special-mass list.
 *
 * AC-MASS-5 asks for a list rather than a cropped table on a phone, so this is
 * the same rows laid out in a column. Times render in Asia/Pontianak from the
 * shared displayTimezone prop, never the device's clock (D-01).
 *
 * No actions here: on a 360px screen a row of buttons competes with the data for
 * the same space.
 */
export function SpecialMassCards({ rows }: { rows: SpecialMassRow[] }) {
    if (rows.length === 0) {
        return null;
    }

    return (
        <ul className="space-y-3 md:hidden">
            {rows.map((mass) => (
                <li
                    key={mass.id}
                    className="rounded-lg border border-hairline p-4"
                >
                    <div className="flex items-start justify-between gap-3">
                        <div className="space-y-1">
                            <Text as="p" variant="body-strong">
                                {mass.title}
                            </Text>

                            <Text as="p" variant="caption" color="muted">
                                {formatWibDateTime(mass.start_at)}
                            </Text>

                            <Text as="p" variant="caption" color="muted">
                                {mass.location.name}
                            </Text>
                        </div>

                        <StatusBadge active={mass.is_active} />
                    </div>
                </li>
            ))}
        </ul>
    );
}
