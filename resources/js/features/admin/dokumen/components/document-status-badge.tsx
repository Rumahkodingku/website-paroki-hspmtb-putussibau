import { Badge } from '@/components/ui/badge';
import type { DocumentStatus } from '../types';

const LABELS: Record<DocumentStatus, string> = {
    draft: 'Draf',
    published: 'Terbit',
    archived: 'Arsip',
};

const VARIANTS = {
    draft: 'secondary',
    published: 'default',
    archived: 'outline',
} as const;

export function DocumentStatusBadge({
    status,
    label,
}: {
    status: DocumentStatus;
    label?: string;
}) {
    return <Badge variant={VARIANTS[status]}>{label ?? LABELS[status]}</Badge>;
}
