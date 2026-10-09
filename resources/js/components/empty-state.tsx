import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';
import { Text } from '@/components/text';
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
                {/*
                    caption-strong rather than a heading step, on purpose.
                    An empty state is a status message sitting inside whatever
                    section the page already headed, so it should not compete
                    with that section's own heading - and defaulting to level 3
                    keeps it from adding a sibling at h2.
                */}
                <Text
                    as={level === 2 ? 'h2' : 'h3'}
                    variant="caption-strong"
                    color="foreground"
                >
                    {title}
                </Text>

                {description && (
                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        className="mx-auto max-w-md"
                    >
                        {description}
                    </Text>
                )}
            </div>

            {action && <div className="pt-1">{action}</div>}
        </div>
    );
}
