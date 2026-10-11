import { Badge } from '@/components/ui/badge';
import type { EventStatus } from '../types';

/**
 * An activity's status, as a word rather than as a colour.
 *
 * The same rule as PostStatusBadge, and for the same reason: roadmap 4.1 says
 * status may not be signalled by colour alone. Here it matters more than on the
 * article list, because all three states here are the same shape of event with
 * the same time and the same place, and a colour-coded dot would leave a
 * cancelled activity indistinguishable from a running one at a glance.
 */
export function EventStatusBadge({ status }: { status: EventStatus }) {
    return (
        <Badge
            variant={
                status === 'cancelled'
                    ? 'destructive'
                    : status === 'postponed'
                      ? 'outline'
                      : 'default'
            }
        >
            {STATUS_LABEL[status]}
        </Badge>
    );
}

/**
 * Whether the activity appears on the public agenda.
 *
 * Separate from the status badge, for the reason the pinned badge is separate
 * on the article list: `is_published` and `status` answer two different
 * questions. An activity can be scheduled and unpublished, or cancelled and
 * still published so the cancellation is visible — which is the case this
 * module cares about most, since PRD EVT-08 asks for a cancelled activity to
 * stay on the calendar and say so.
 */
export function PublishedBadge({ published }: { published: boolean }) {
    if (!published) {
        return (
            <Badge
                variant="secondary"
                title="Tidak tampil di halaman agenda publik"
            >
                Tidak diumumkan
            </Badge>
        );
    }

    return null;
}

const STATUS_LABEL: Record<EventStatus, string> = {
    scheduled: 'Terjadwal',
    cancelled: 'Dibatalkan',
    postponed: 'Ditunda',
};
