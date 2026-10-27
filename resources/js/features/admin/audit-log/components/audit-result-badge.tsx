import { Badge } from '@/components/ui/badge';

const VARIANTS = {
    success: 'secondary',
    failure: 'destructive',
    denied: 'outline',
} as const;

export function AuditResultBadge({
    result,
    label,
}: {
    result: string;
    label: string;
}) {
    const variant =
        VARIANTS[result as keyof typeof VARIANTS] ?? ('outline' as const);

    return <Badge variant={variant}>{label}</Badge>;
}
