import { Badge } from '@/components/ui/badge';
import type { NoticeStatus } from '../types';

/*
 * The two badges that are about this module rather than about lists.
 *
 * They stayed in features/admin/jadwal-misa/components/ when the generic parts
 * of list-parts.tsx moved up to components/list-parts.tsx. Both read a column
 * that exists only here — is_active on locations and schedules, and the four
 * schedule-notice states — so neither would mean anything to a second feature.
 *
 * ARCHITECTURE.md Part B section 1.1: one consumer means it belongs to the
 * feature.
 */

export function NoticeStatusBadge({ status }: { status: NoticeStatus }) {
    const label: Record<NoticeStatus, string> = {
        draft: 'Nonaktif',
        scheduled: 'Terjadwal',
        visible: 'Sedang tampil',
        expired: 'Kedaluwarsa',
    };

    return (
        <Badge variant={status === 'visible' ? 'default' : 'secondary'}>
            {label[status]}
        </Badge>
    );
}
