import { Badge } from '@/components/ui/badge';

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
    status,
    label,
    isPublished = null,
}: {
    status: string;
    label: string;
    /** Only meaningful for events; null elsewhere. */
    isPublished?: boolean | null;
}) {
    if (isPublished === false) {
        return <Badge variant="secondary">Draf</Badge>;
    }

    return <Badge variant={VARIANTS[status] ?? 'secondary'}>{label}</Badge>;
}
