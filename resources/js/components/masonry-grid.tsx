import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * MasonryGrid - the layout shell both public galleries render into.
 *
 * CSS multi-column, not a JavaScript layout library, for three reasons that
 * all still hold under DESIGN-v2:
 *
 *   1. It needs no dependency and no measurement. `columns` balances the
 *      columns itself, so there is no resize observer and no layout pass.
 *   2. Every tile keeps its own aspect ratio, which is the whole point of a
 *      masonry. A crop would flatten the variation and defeat it.
 *   3. It degrades: with `break-inside-avoid` missing, a tile simply may split
 *      across a column boundary - ugly, not broken, and readable.
 *
 * The cost is column-major order: items fill the first column downwards before
 * the second. Tab order follows the DOM, which matches what is on screen, so
 * the two never disagree - but a gallery whose sequence is meant to read
 * left-to-right is not what this is. Recorded in DECISIONS.md D-75.
 *
 * Base is `columns-2`; a caller adds its own breakpoints through `className`.
 * A breakpoint variant outranks the unprefixed base in Tailwind, so passing
 * `sm:columns-3 lg:columns-4` does not fight the base.
 */
export function MasonryGrid({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <ul className={cn('columns-2 gap-3 [column-fill:balance]', className)}>
            {children}
        </ul>
    );
}

/**
 * One tile. Two classes carry the whole layout: `break-inside-avoid` stops a
 * photo being split down the middle by a column break, and the bottom margin is
 * the row gap - `gap-3` on a multi-column container only sets the column gap,
 * because there are no rows.
 */
export function MasonryItem({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <li className={cn('mb-3 break-inside-avoid', className)}>{children}</li>
    );
}

/**
 * The tile's aspect ratio, from the media row's own dimensions.
 *
 * Passing this to the image is what stops the page from jumping: the browser
 * reserves the height before the file is fetched, so a lazy image arriving late
 * moves nothing. Without `width`/`height` - a row that predates dimension
 * capture - the tile falls back to `4 / 3` rather than to nothing, which is
 * what `ParishImage` would otherwise use.
 */
export function aspectRatioFrom(
    width: number | null | undefined,
    height: number | null | undefined,
    fallback: string = '4 / 3',
): string {
    if (
        typeof width === 'number' &&
        typeof height === 'number' &&
        width > 0 &&
        height > 0
    ) {
        return `${width} / ${height}`;
    }

    return fallback;
}
