import { cva, type VariantProps } from 'class-variance-authority';
import { type ComponentProps, type ReactNode } from 'react';
import { cn } from '@/lib/utils';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const parishCardVariants = cva('transition-colors', {
    variants: {
        variant: {
            default: 'border-hairline bg-card text-card-foreground',
            /*
             * secondary, not a literal bg-navy-light.
             *
             * DESIGN.md components.agenda-card names navy-light, and in light
             * mode secondary IS navy-light - so nothing changes visually there.
             * The literal did not survive dark mode: navy-light is a near-white
             * surface and text-foreground in dark mode is a near-white ink, so
             * the card measured 1.04:1 and disappeared. secondary is navy-light in
             * light and dark-navy-soft in dark, which is the entire reason a
             * semantic token exists.
             *
             * The dark:text override is the same split one level down: DESIGN.md
             * gives the agenda card ink text in light mode, and that ink is
             * unreadable on the dark surface.
             */
            agenda: 'border-transparent bg-secondary text-foreground dark:text-secondary-foreground',
        },
        interactive: {
            true: 'focus-within:border-primary hover:border-primary',
            false: '',
        },
    },
    defaultVariants: {
        variant: 'default',
        interactive: false,
    },
});

type ParishCardRootProps = ComponentProps<typeof Card> &
    VariantProps<typeof parishCardVariants> & {
        children?: ReactNode;
    };

function ParishCard({
    className,
    variant,
    interactive,
    ...props
}: ParishCardRootProps) {
    return (
        <Card
            className={cn(
                parishCardVariants({ variant, interactive }),
                className,
            )}
            {...props}
        />
    );
}

/*
 * The shadcn sub-components are re-exported under parish names rather than
 * wrapped. They carry no parish-specific geometry - the padding and radius are
 * already restyled inside ui/card.tsx - so a wrapper would be an identity
 * function with nothing to do. Re-exporting keeps the import site honest about
 * which layer owns the layout and which owns the visual language.
 */
export {
    CardContent as ParishCardContent,
    CardDescription as ParishCardDescription,
    CardFooter as ParishCardFooter,
    CardHeader as ParishCardHeader,
    CardTitle as ParishCardTitle,
};

export { ParishCard, parishCardVariants };
