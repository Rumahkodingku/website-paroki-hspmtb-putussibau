import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';
import { cn } from '@/lib/utils';

/*
 * Restyled onto docs/DESIGN-v2.md §9.
 *
 *   variant default    -> primary action   red fill, white text, pill
 *   variant secondary  -> secondary action neutral surface, semantic border
 *   variant outline    -> red outline      red border/text, fills red on hover
 *   variant ghost      -> quiet action     text only, neutral hover surface
 *   variant link       -> tertiary link    red text, underline on hover
 *   variant gold       -> ceremonial only  named, never the default action
 *   size    icon       -> 44x44 hit area
 *
 * The primary action uses bg-primary with an explicit hover and active token
 * (primary-hover / primary-active) and a 44px minimum height. Gold is never the
 * default; it only appears when a page asks for it by name.
 *
 * outline and link use primary-on-surface for red as BORDER and TEXT: in dark
 * mode the brand red has no luminance left against the dark canvas (2.28:1), so
 * that token points at a lighter tint there, while light mode is unchanged.
 *
 * @see docs/DECISIONS.md D-32, D-72
 */
const buttonVariants = cva(
    "inline-flex shrink-0 items-center justify-center gap-2 font-medium whitespace-nowrap transition-[color,background-color,border-color,box-shadow,transform] duration-150 outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
    {
        variants: {
            variant: {
                default:
                    'rounded-pill bg-primary text-primary-foreground hover:bg-primary-hover active:bg-primary-active',
                destructive:
                    'rounded-pill bg-destructive text-destructive-foreground hover:bg-destructive/90',
                secondary:
                    'rounded-pill border border-input bg-background text-foreground hover:bg-muted',
                outline:
                    'rounded-pill border border-primary-on-surface bg-background text-primary-on-surface hover:bg-primary hover:text-primary-foreground',
                gold: 'rounded-pill bg-gold text-navy-deep hover:bg-gold/90',
                ghost: 'rounded-pill text-foreground hover:bg-muted hover:text-foreground',
                link: 'text-primary-on-surface underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-11 px-[22px] text-body',
                sm: 'h-9 px-4 text-label',
                lg: 'h-12 px-8 text-body-lg',
                icon: 'size-11 rounded-full',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

function Button({
    className,
    variant,
    size,
    asChild = false,
    ...props
}: React.ComponentProps<'button'> &
    VariantProps<typeof buttonVariants> & {
        asChild?: boolean;
    }) {
    const Comp = asChild ? Slot : 'button';

    return (
        <Comp
            data-slot="button"
            className={cn(buttonVariants({ variant, size, className }))}
            {...props}
        />
    );
}

export { Button, buttonVariants };
