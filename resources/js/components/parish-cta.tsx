import { type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type ParishCtaProps = {
    title: string;
    description?: string;
    /** The single primary action. Usually a Button wrapping a Link. */
    children?: ReactNode;
    /** Label only. Rendered as an outline button by this component. */
    secondaryLabel?: string;
    level?: 2 | 3;
    /** Centre the copy. The default suits a full-width band between sections. */
    align?: 'left' | 'center';
    className?: string;
};

export function ParishCta({
    title,
    description,
    children,
    secondaryLabel,
    level = 2,
    align = 'left',
    className,
}: ParishCtaProps) {
    const Heading = level === 2 ? 'h2' : 'h3';

    return (
        <div
            className={cn(
                'flex flex-col gap-6 rounded-lg bg-navy p-8 text-white md:p-12',
                align === 'center' && 'items-center text-center',
                className,
            )}
        >
            <div
                className={cn(
                    'flex flex-col gap-3',
                    align === 'center' && 'mx-auto max-w-2xl',
                )}
            >
                <Heading className="font-display text-display-md text-balance">
                    {title}
                </Heading>

                {description && (
                    <p className="text-lead text-navy-light">{description}</p>
                )}
            </div>

            {(children || secondaryLabel) && (
                <div
                    className={cn(
                        // Horizontal on a desktop, wrapped then stacked as the
                        // column narrows. flex-wrap rather than a breakpoint so
                        // the buttons reflow before the container does.
                        'flex flex-wrap items-center gap-3',
                        align === 'center' && 'justify-center',
                    )}
                >
                    {children}

                    {secondaryLabel && (
                        <Button variant="outline" size="lg">
                            {secondaryLabel}
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}

export default ParishCta;
