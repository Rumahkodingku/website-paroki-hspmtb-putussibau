import { type ReactNode } from 'react';
import { Text } from '@/components/text';
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
                    <Text as="p" variant="caption-strong" color="danger">
                        {eyebrow}
                    </Text>
                )}

                {/*
                    `level` decides the element and `variant` decides the
                    style, which is the separation that lets a subsection drop
                    to h3 without the size changing with it. Spelled out as a
                    ternary rather than a template so `as` stays a literal and
                    keeps resolving its props against the right tag.
                */}
                <Text as={level === 2 ? 'h2' : 'h3'} variant="display-md">
                    {title}
                </Text>

                {description && (
                    <Text
                        as="p"
                        variant="lead"
                        color="muted"
                        className="max-w-2xl"
                    >
                        {description}
                    </Text>
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
