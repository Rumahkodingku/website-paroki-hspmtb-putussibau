import { Text } from '@/components/text';
import { formatWibDateTime } from '@/lib/datetime';
import { NoticeStatusBadge } from '../../../components/list-parts';
import type { ScheduleNoticeRow } from '../../../types';

/*
 * The mobile rendering of the notice list.
 *
 * AC-MASS-5 asks for a list rather than a cropped table on a phone, so this is
 * the same rows laid out in a column. Times render in Asia/Pontianak from the
 * shared displayTimezone prop, never the device's clock (D-01).
 *
 * The "Target terhapus" line is here for the same reason it is in the table: a
 * notice whose target was soft-deleted has quietly stopped appearing on the
 * public page, and this is the only place an admin can see that (D-40).
 */
export function ScheduleNoticeCards({
    rows,
    displayTimezone,
    scopeLabel,
}: {
    rows: ScheduleNoticeRow[];
    displayTimezone?: string | null;
    scopeLabel: (notice: ScheduleNoticeRow) => string;
}) {
    if (rows.length === 0) {
        return null;
    }

    return (
        <ul className="space-y-3 md:hidden">
            {rows.map((notice) => (
                <li
                    key={notice.id}
                    className="rounded-lg border border-hairline p-4"
                >
                    <div className="flex items-start justify-between gap-3">
                        <div className="space-y-1">
                            <Text as="p" variant="body-strong">
                                {notice.title}
                            </Text>

                            <Text as="p" variant="caption" color="muted">
                                {scopeLabel(notice)}
                            </Text>

                            <Text as="p" variant="caption" color="muted">
                                {formatWibDateTime(
                                    notice.starts_at,
                                    displayTimezone,
                                )}
                            </Text>

                            {notice.target_deleted ? (
                                <Text
                                    as="p"
                                    variant="micro-legal"
                                    color="muted"
                                >
                                    Target terhapus
                                </Text>
                            ) : null}
                        </div>

                        <NoticeStatusBadge status={notice.status} />
                    </div>
                </li>
            ))}
        </ul>
    );
}
