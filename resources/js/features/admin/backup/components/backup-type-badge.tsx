import { Database, Package } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { BackupType } from '../types';

export function BackupTypeBadge({
    type,
    label,
}: {
    type: BackupType;
    label: string;
}) {
    return (
        <Badge variant="outline" className="gap-1.5">
            {type === 'database' ? (
                <Database className="size-3.5" aria-hidden="true" />
            ) : (
                <Package className="size-3.5" aria-hidden="true" />
            )}
            {label}
        </Badge>
    );
}
