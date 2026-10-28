import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    Loader2,
    ShieldAlert,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const VARIANTS = {
    queued: 'secondary',
    running: 'secondary',
    validating: 'secondary',
    preparing: 'secondary',
    ready: 'outline',
    success: 'default',
    passed: 'default',
    failed: 'destructive',
    partial: 'destructive',
    expired: 'outline',
    pending: 'outline',
} as const;

type Status = keyof typeof VARIANTS;

export function BackupStatusBadge({
    status,
    label,
}: {
    status: Status;
    label: string;
}) {
    const spinning =
        status === 'running' ||
        status === 'validating' ||
        status === 'preparing';

    return (
        <Badge variant={VARIANTS[status]} className="gap-1.5">
            {spinning ? (
                <Loader2 className="size-3.5 animate-spin" aria-hidden="true" />
            ) : status === 'success' || status === 'passed' ? (
                <CheckCircle2 className="size-3.5" aria-hidden="true" />
            ) : status === 'failed' || status === 'partial' ? (
                <AlertTriangle className="size-3.5" aria-hidden="true" />
            ) : status === 'expired' ? (
                <ShieldAlert className="size-3.5" aria-hidden="true" />
            ) : (
                <Clock3 className="size-3.5" aria-hidden="true" />
            )}
            {label}
        </Badge>
    );
}
