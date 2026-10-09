import { type ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type SectionHeadingProps = {
    /** The heading itself. Keep it short; the document asks for scannable ones. */
    title: string;
    /** Small label above the title. Red by design. */
    eyebrow?: string;
    description?: string;
    /** Usually a "Lihat selengkapnya" link. Rendered beside the heading. */
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
    const Heading = level === 2 ? 'h2' : 'h3';

    const centred = align === 'center';

    return (
        <div
            className={cn(
                'flex flex-col gap-6 md:flex-row md:items-end md:justify-between',
                centred && 'md:flex-col md:items-center',
                className,
            )}
        >
            <div
                className={cn(
                    'flex flex-col gap-3',
                    centred && 'mx-auto max-w-2xl items-center text-center',
                )}
            >
                {eyebrow && (
                    <p className="text-caption-strong text-red-on-surface">
                        {eyebrow}
                    </p>
                )}

                <Heading className="font-display text-display-md text-balance">
                    {title}
                </Heading>

                {description && (
                    <p className="max-w-2xl text-lead text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>

            {action && (
                <div className="flex shrink-0 flex-wrap items-center gap-3">
                    {action}
                </div>
            )}
        </div>
    );
}

export default SectionHeading;
