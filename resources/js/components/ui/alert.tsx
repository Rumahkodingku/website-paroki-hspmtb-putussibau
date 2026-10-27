import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

/*
 * Restyled onto docs/DESIGN-v2.md.
 *
 * The destructive variant renders the red as TEXT on a red-tinted surface. The
 * brand red has no luminance left on that dark surface, so it uses
 * primary-on-surface, which is the brand red in light and the lighter accessible
 * tint in dark. Destructive itself stays the brand red where it is a fill: white
 * on #AB020E measures 7.65:1.
 *
 * @see docs/DECISIONS.md D-32, D-72
 */
const alertVariants = cva(
    'relative grid w-full grid-cols-[0_1fr] items-start gap-y-0.5 rounded-lg border px-4 py-3 text-sm has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] has-[>svg]:gap-x-3 [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current',
    {
        variants: {
            variant: {
                default: 'bg-background text-foreground',
                destructive:
                    'border-destructive/20 bg-primary-soft text-primary-on-surface *:data-[slot=alert-description]:text-primary-on-surface/80 [&>svg]:text-current',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

function Alert({
    className,
    variant,
    ...props
}: React.ComponentProps<'div'> & VariantProps<typeof alertVariants>) {
    return (
        <div
            data-slot="alert"
            role="alert"
            className={cn(alertVariants({ variant }), className)}
            {...props}
        />
    );
}

function AlertTitle({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="alert-title"
            className={cn(
                'col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight',
                className,
            )}
            {...props}
        />
    );
}

function AlertDescription({
    className,
    ...props
}: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="alert-description"
            className={cn(
                'col-start-2 grid justify-items-start gap-1 text-sm text-muted-foreground [&_p]:leading-relaxed',
                className,
            )}
            {...props}
        />
    );
}

export { Alert, AlertTitle, AlertDescription };
