import { type ReactNode } from 'react';

import { Text } from '@/components/text';
import { cn } from '@/lib/utils';

export type SectionHeadingProps = {
    /** Small label above the title. */
    eyebrow?: string;
    /** The heading itself. */
    title: string;
    description?: string;
    /** Usually a link or button rendered beside the heading. */
    action?: ReactNode;
    level?: 2 | 3;
    align?: 'left' | 'center';
    className?: string;
};

export function SectionHeading({
    title,
    eyebrow,
    description,
    action,
    level = 2,
    align = 'left',
    className,
}: SectionHeadingProps) {
    const centred = align === 'center';

    return (
        <div
            className={cn(
                'flex flex-col gap-5 sm:gap-6',
                'md:flex-row md:items-end md:justify-between',
                centred && 'md:flex-col md:items-center',
                className,
            )}
        >
            <div
                className={cn(
                    'flex min-w-0 flex-col gap-2',
                    centred && 'mx-auto max-w-2xl items-center text-center',
                )}
            >
                {eyebrow && (
                    <div
                        className={cn(
                            'flex items-center gap-3',
                            centred && 'justify-center',
                        )}
                    >
                        <span
                            aria-hidden="true"
                            className="h-0.5 w-7 shrink-0 bg-primary"
                        />

                        <Text
                            as="p"
                            variant="label"
                            className="text-[11px] font-semibold tracking-[0.12em] text-primary uppercase"
                        >
                            {eyebrow}
                        </Text>
                    </div>
                )}

                <div className="mt-1">
                    <Text as={level === 2 ? 'h2' : 'h3'} variant="display-md">
                        {title}
                    </Text>

                    {description && (
                        <Text as="p" variant="body" color="muted">
                            {description}
                        </Text>
                    )}
                </div>
            </div>

            {action && (
                <div
                    className={cn(
                        'flex shrink-0 flex-wrap items-center gap-3',
                        centred && 'md:justify-center',
                    )}
                >
                    {action}
                </div>
            )}
        </div>
    );
}

export default SectionHeading;
