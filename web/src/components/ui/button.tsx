import { Button as ButtonPrimitive } from "@base-ui/react/button"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "cn"

// `primary` (Kyrgyz red) is for the one decisive action on a screen; everything else uses `lake` or quieter variants.
const buttonVariants = cva(
  "inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-transparent font-semibold whitespace-nowrap transition-colors outline-none select-none focus-visible:ring-2 focus-visible:ring-lake focus-visible:ring-offset-2 active:translate-y-px disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
  {
    variants: {
      variant: {
        primary: "bg-kyrgyz text-white shadow-sm hover:bg-kyrgyz-hover",
        lake: "bg-lake text-white hover:bg-lake-hover",
        meadow: "bg-meadow-50 text-meadow hover:bg-meadow hover:text-white",
        outline: "border-line-strong bg-white text-ink hover:bg-snow",
        soft: "bg-secondary text-ink hover:bg-line",
        ghost: "text-ink-muted hover:bg-secondary hover:text-ink",
        link: "px-0 text-lake underline-offset-4 hover:underline",
      },
      size: {
        sm: "h-9 px-3 text-sm",
        md: "h-11 px-4 text-sm",
        lg: "h-12 px-6 text-base",
        icon: "size-10",
      },
    },
    defaultVariants: {
      variant: "lake",
      size: "md",
    },
  }
)

function Button({
  className,
  variant,
  size,
  ...props
}: ButtonPrimitive.Props & VariantProps<typeof buttonVariants>) {
  return (
    <ButtonPrimitive
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
