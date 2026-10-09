import { Slot } from "@radix-ui/react-slot";
import { cva, type VariantProps } from "class-variance-authority";
import * as React from "react";
import { cn } from "@/lib/utils";

/*
 * Restyled onto docs/DESIGN.md.
 *
 *   variant default    -> components.button-primary         red, pill
 *   variant outline    -> components.button-secondary-pill  red border, red text
 *   variant secondary  -> components.button-navy            navy CTA
 *   variant gold       -> components.button-gold            ceremonial only
 *   size    icon       -> components.button-icon-circular   44px
 *
 * Two rules from the document shape the values. Gold is never the default
 * action, it only appears when a page asks for it by name. And the pressed
 * state is a small scale reduction rather than a colour change, which is what
 * components.button-primary-active specifies.
 *
 * outline and link carry one deviation, and it is the same one as everywhere
 * else red appears: they need red as a BORDER and as TEXT, and DESIGN.md
 * v1.1's semantic.dark.primary is the brand red, which is 2.28:1 on the dark
 * canvas. red-on-surface is primary in light - so light mode looks identical -
 * and primary-on-dark in dark, which is 6.14:1 and clears the 3:1 an outline
 * needs from WCAG 1.4.11. Their hover state still fills with bg-primary and
 * puts white on it at 7.65:1, which is where the brand red belongs.
 *
 * @see docs/DECISIONS.md D-32
 */
const buttonVariants = cva(
  "inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap font-medium transition-[color,background-color,border-color,box-shadow,transform] active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
  {
    variants: {
      variant: {
        default:
          "rounded-pill bg-primary text-primary-foreground hover:bg-primary-hover",
        destructive:
          "rounded-pill bg-destructive text-destructive-foreground hover:bg-destructive/90",
        outline:
          "rounded-pill border border-red-on-surface bg-background text-red-on-surface hover:bg-primary hover:text-primary-foreground",
        secondary: "rounded-pill bg-navy text-white hover:bg-navy/90",
        gold: "rounded-pill bg-gold text-navy-dark hover:bg-gold-dark",
        ghost: "rounded-pill text-foreground hover:bg-accent hover:text-accent-foreground",
        link: "text-red-on-surface underline-offset-4 hover:underline",
      },
            size: {
                default: "h-11 px-[22px] text-body",
                sm: "h-9 px-4 text-button-utility",
                lg: "h-12 px-8 text-button-large",
                icon: "size-11 rounded-full",
            },
        },
        defaultVariants: {
            variant: "default",
            size: "default",
        },
    },
);

function Button({
    className,
    variant,
    size,
    asChild = false,
    ...props
}: React.ComponentProps<"button"> &
    VariantProps<typeof buttonVariants> & {
        asChild?: boolean;
    }) {
    const Comp = asChild ? Slot : "button";

    return (
        <Comp
            data-slot="button"
            className={cn(buttonVariants({ variant, size, className }))}
            {...props}
        />
    );
}

export { Button, buttonVariants };
