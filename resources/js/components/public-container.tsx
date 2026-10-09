import { type ElementType, type ReactNode } from 'react';
import { cn } from '@/lib/utils';

/*
 * PublicContainer - DESIGN.md, "Grid & Container".
 *
 * One definition of the horizontal measure for the whole public site. Every
 * navbar row, footer block, section and page puts its content inside this, so
 * the left edge of the header, the body and the footer are the same line at
 * every viewport. That is the entire job: this component exists so the padding
 * below is written once and cannot drift between pages.
 *
 * max-w-7xl is the document's "Standard application/content container". `wide`
 * lifts it for a full editorial composition - a photographic homepage - where
 * DESIGN.md allows up to 1440px. It is the exception, not the default.
 *
 * Gutter is 16px on a small phone and grows to 32px on a desktop, which keeps
 * 360px readable (NFR-RESP) without making a 1440px screen feel like a form.
 *
 * Note what this does NOT do: it does not constrain vertical rhythm. That is
 * PublicSection's job. Keeping the two apart is what lets a page choose one
 * without getting the other as a side effect.
 */

export type PublicContainerProps = {
    children: ReactNode;
    /** Wide editorial composition, up to DESIGN.md's 1440px ceiling. */
    width?: 'default' | 'wide';
    /** Drop the horizontal gutter. For full-bleed imagery a page owns itself. */
    bleed?: boolean;
    as?: ElementType;
    className?: string;
};

export function PublicContainer({
    children,
    width = 'default',
    bleed = false,
    as: Tag = 'div',
    className,
}: PublicContainerProps) {
    return (
        <Tag
            className={cn(
                'mx-auto w-full',
                width === 'wide' ? 'max-w-[1440px]' : 'max-w-7xl',
                !bleed && 'px-4 sm:px-6 lg:px-8',
                className,
            )}
        >
            {children}
        </Tag>
    );
}

export default PublicContainer;
