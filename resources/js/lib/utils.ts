import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { extendTailwindMerge } from 'tailwind-merge';

/*
 * The typography tokens are registered with tailwind-merge, and they have to be.
 *
 * tailwind-merge knows Tailwind's own scales and nothing else. Every `text-*`
 * class it cannot classify is assumed to be a COLOUR, because `text-` is
 * Tailwind's prefix for both. So out of the box:
 *
 *     cn('text-body', 'text-foreground')  =>  'text-foreground'
 *
 * The size is silently discarded. That is not hypothetical: it is what
 * components/ui/button.tsx and components/ui/badge.tsx were doing. Every one of
 * Button's three size variants carried a token (`text-body`,
 * `text-button-utility`, `text-button-large`) into cn(), met a `text-*` colour
 * from its variant, and lost it — so every Button in the application was
 * rendering at the browser's default 16px regardless of the size asked for,
 * and Badge was doing the same with `text-micro-legal`.
 *
 * Listing the sixteen tokens in the `font-size` group fixes the classification.
 * Colours are deliberately NOT listed: they already fall through to
 * tailwind-merge's own text-colour group, which handles both the semantic names
 * and any arbitrary value, so adding them here would change nothing.
 *
 * Only the sixteen names below are affected. `text-sm`, `text-2xl` and
 * `text-[13px]` keep working exactly as before — they were always recognised —
 * and they now correctly conflict with a design token, since a caller passing
 * `className="text-2xl"` means to win.
 *
 * @see docs/DECISIONS.md D-33
 */
const DESIGN_FONT_SIZE_TOKENS = [
    'hero-display',
    'display-lg',
    'display-md',
    'lead',
    'lead-airy',
    'tagline',
    'body-strong',
    'body',
    'dense-link',
    'caption',
    'caption-strong',
    'button-large',
    'button-utility',
    'fine-print',
    'micro-legal',
    'nav-link',
] as const;

const twMerge = extendTailwindMerge({
    extend: {
        classGroups: {
            'font-size': [{ text: [...DESIGN_FONT_SIZE_TOKENS] }],
        },
    },
});

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}
