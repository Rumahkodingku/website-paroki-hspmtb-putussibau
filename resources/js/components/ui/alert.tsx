import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

/*
 * Restyled onto docs/DESIGN.md, and the destructive variant is the reason this
 * file is one of the four AGENTS.md calls out as deliberately edited.
 *
 * DESIGN.md maps accent-foreground to gold-dark and destructive to primary, and
 * both are followed except where a value cannot be read. destructive-text is the
 * case: the alert renders the destructive colour as TEXT on a red-tinted
 * surface, and #AB020E on red-soft-dark measures 2.08:1. red-on-surface points at
 * primary-on-dark in dark mode, which measures 5.61:1 on the same surface.
 *
 * Destructive itself is left alone, because it is right where it is used as a
 * fill: a destructive button is white on #AB020E, which is 7.65:1. The split is
 * the same one every other token in app.css had to make.
 *
 * @see docs/DECISIONS.md D-32
 */
const alertVariants = cva(
  "relative w-full rounded-lg border px-4 py-3 text-sm grid has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] grid-cols-[0_1fr] has-[>svg]:gap-x-3 gap-y-0.5 items-start [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current",
  {
    variants: {
      variant: {
        default: "bg-background text-foreground",
        destructive:
          "border-destructive/20 bg-red-soft text-red-on-surface [&>svg]:text-current *:data-[slot=alert-description]:text-red-on-surface/80",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Alert({
  className,
  variant,
  ...props
}: React.ComponentProps<"div"> & VariantProps<typeof alertVariants>) {
  return (
    <div
      data-slot="alert"
      role="alert"
      className={cn(alertVariants({ variant }), className)}
      {...props}
    />
  )
}

function AlertTitle({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="alert-title"
      className={cn(
        "col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight",
        className
      )}
      {...props}
    />
  )
}

function AlertDescription({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="alert-description"
      className={cn(
        "text-muted-foreground col-start-2 grid justify-items-start gap-1 text-sm [&_p]:leading-relaxed",
        className
      )}
      {...props}
    />
  )
}

export { Alert, AlertTitle, AlertDescription }
