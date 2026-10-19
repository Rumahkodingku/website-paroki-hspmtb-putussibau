import { Badge } from '@/components/ui/badge';
import type { DashboardContentType } from '../types';

/*
 * The status chip for a dashboard row.
 *
 * Feature-local on purpose: features/admin/berita owns PostStatusBadge and
 * ArchitectureTest forbids one feature importing another, so the dashboard
 * cannot borrow it. What it can do is stop the dashboard from inventing a
 * second English vocabulary — the label always comes from the backend enum, and
 * only the colour is decided here.
 *
 * Status is never signalled by colour alone; the label is always present.
 */

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

const VARIANTS: Record<string, BadgeVariant> = {
    draft: 'secondary',
    scheduled: 'default',
    published: 'default',
    archived: 'outline',
    cancelled: 'destructive',
    postponed: 'outline',
};

export function ContentStatusBadge({
    type,
    status,
    label,
    isPublished = null,
}: {
    type: DashboardContentType;
    status: string;
    label: string;
    /** Only meaningful for events; null elsewhere. */
    isPublished?: boolean | null;
}) {
    // An unpublished activity is a draft first and an activity second, which is
    // the same order the Agenda list uses. The activity status still shows once
    // it is published.
    if (type === 'event' && isPublished === false) {
        return <Badge variant="secondary">Draf</Badge>;
    }

    return <Badge variant={VARIANTS[status] ?? 'secondary'}>{label}</Badge>;
}
