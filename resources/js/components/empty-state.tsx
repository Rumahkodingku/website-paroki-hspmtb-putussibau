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
    /** Heading level, so a page can keep its outline intact.  */
    level?: 2 | 3;
    className?: string;
};

export function EmptyState({
    title,
    description,
    icon: Icon,
    action,
    level = 3,
    className,
}: EmptyStateProps) {
    const Heading = level === 2 ? 'h2' : 'h3';

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
                <Heading className="text-caption-strong text-foreground">
                    {title}
                </Heading>

                {description && (
                    <p className="mx-auto max-w-md text-body text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>

            {action && <div className="pt-1">{action}</div>}
        </div>
    );
}
