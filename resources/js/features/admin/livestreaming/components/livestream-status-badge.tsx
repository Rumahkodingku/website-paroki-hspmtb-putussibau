import { Badge } from '@/components/ui/badge';
import type {
    LivestreamBroadcastStatus,
    LivestreamEditorialStatus,
} from '../types';

/**
 * Draft / Terbit / Diarsipkan as a labelled badge.
 *
 * Text, not colour alone: a status the reader can only tell apart by its colour
 * is one they cannot tell apart at all.
 */
export function LivestreamEditorialBadge({
    status,
    label,
}: {
    status: LivestreamEditorialStatus;
    label: string;
}) {
    const variant = status === 'published' ? 'default' : 'secondary';

    return <Badge variant={variant}>{label}</Badge>;
}

/**
 * Scheduled / Sedang Live / Selesai / Tidak Tersedia.
 *
 * Live is the one state that earns the brand fill; everything else is quiet so
 * the live row is obvious at a glance.
 */
export function LivestreamBroadcastBadge({
    status,
    label,
}: {
    status: LivestreamBroadcastStatus;
    label: string;
}) {
    const variant = status === 'live' ? 'default' : 'outline';

    return <Badge variant={variant}>{label}</Badge>;
}
