import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

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
 * Ring colours come from --ring, which is primary-focus in both themes.
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
          "rounded-pill border border-primary bg-background text-primary hover:bg-primary hover:text-primary-foreground",
        secondary: "rounded-pill bg-navy text-white hover:bg-navy/90",
        gold: "rounded-pill bg-gold text-navy-dark hover:bg-gold-dark",
        ghost: "rounded-pill text-foreground hover:bg-accent hover:text-accent-foreground",
        link: "text-primary underline-offset-4 hover:underline",
      },
      size: {
        // 11px 22px padding, per components.button-primary.
        default: "h-11 px-[22px] text-body",
        sm: "h-9 px-4 text-button-utility",
        lg: "h-12 px-8 text-button-large",
        // 44px minimum touch target from the responsive section.
        icon: "size-11 rounded-full",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
