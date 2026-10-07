import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';
import { cn } from '@/lib/utils';

type EmptyStateProps = {
    title: string;
    description?: string;
    /** Optional icon. Keep it contextual; a decorative glyph adds nothing. */
    icon?: LucideIcon;
    /** Primary call to action, such as "Create the first one". */
    action?: ReactNode;
    className?: string;
};

/**
 * Shown when a list or section has no content yet.
 *
 * Every data-driven view needs an empty state, so this is a primitive rather
 * than a per-page block. The surface stays flat: DESIGN.md asks for surface
 * changes rather than decorative chrome, and a bordered box would compete with
 * the content that replaces it later.
 *
 * Copy should say what will appear here and what to do next. "No data" on its
 * own leaves the reader stuck.
 */
export function EmptyState({
    title,
    description,
    icon: Icon,
    action,
    className,
}: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-3 rounded-lg px-6 py-14 text-center',
                className,
            )}
        >
            {Icon && (
                <div
                    className="flex size-11 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    aria-hidden="true"
                >
                    <Icon className="size-5" />
                </div>
            )}

            <div className="space-y-1">
                <h3 className="text-base font-semibold">{title}</h3>

                {description && (
                    <p className="mx-auto max-w-md text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>

            {action && <div className="pt-1">{action}</div>}
        </div>
    );
}
