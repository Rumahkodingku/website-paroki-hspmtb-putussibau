import { Badge } from '@/components/ui/badge';
import type { CommunityStatusValue } from '../types';

const STATUS_VARIANT: Record<
    CommunityStatusValue,
    'default' | 'secondary' | 'outline'
> = {
    active: 'default',
    forming: 'outline',
    inactive: 'secondary',
};

export function CommunityStatusBadge({
    status,
    label,
}: {
    status: CommunityStatusValue;
    label: string;
}) {
    return <Badge variant={STATUS_VARIANT[status]}>{label}</Badge>;
}

export function PublicationBadge({ published }: { published: boolean }) {
    return (
        <Badge variant={published ? 'default' : 'secondary'}>
            {published ? 'Dipublikasikan' : 'Draf'}
        </Badge>
    );
}
