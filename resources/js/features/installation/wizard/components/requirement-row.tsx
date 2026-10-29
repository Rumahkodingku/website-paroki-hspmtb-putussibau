import { AlertTriangle, CheckCircle2, XCircle } from 'lucide-react';
import { Text } from '@/components/text';
import { cn } from '@/lib/utils';
import type { RequirementCheck } from '../types';

const STYLES = {
    pass: {
        icon: CheckCircle2,
        iconClass: 'text-emerald-600 dark:text-emerald-400',
        label: 'Lulus',
        badge: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
    warning: {
        icon: AlertTriangle,
        iconClass: 'text-amber-600 dark:text-amber-400',
        label: 'Peringatan',
        badge: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    },
    fail: {
        icon: XCircle,
        iconClass: 'text-primary-on-surface',
        label: 'Gagal',
        badge: 'bg-destructive/10 text-primary-on-surface',
    },
} as const;

export default function RequirementRow({ check }: { check: RequirementCheck }) {
    const style = STYLES[check.status];
    const Icon = style.icon;

    return (
        <li className="flex items-start gap-3 py-4">
            <Icon
                aria-hidden="true"
                className={cn('mt-0.5 size-5 shrink-0', style.iconClass)}
            />

            <div className="flex min-w-0 flex-1 flex-col gap-1">
                <div className="flex flex-wrap items-center gap-2">
                    <Text as="span" variant="body" weight="semibold">
                        {check.label}
                    </Text>

                    <span
                        className={cn(
                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-fine-print font-medium',
                            style.badge,
                        )}
                    >
                        {style.label}
                    </span>
                </div>

                <Text as="p" variant="body-sm" color="muted">
                    {check.detail}
                </Text>

                {check.action ? (
                    <Text as="p" variant="body-sm" color="muted">
                        {check.action}
                    </Text>
                ) : null}
            </div>
        </li>
    );
}
