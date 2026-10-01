import { Star } from "lucide-react";
import { cn } from "cn";
import type { RatingSummary } from "@/lib/catalog";

export function Rating({ rating, className }: { rating: RatingSummary | null; className?: string }) {
  if (!rating) return null;
  return (
    <span className={cn("inline-flex items-center gap-1 text-sm", className)}>
      <Star className="size-4 fill-sun text-sun" aria-hidden="true" />
      <span className="font-bold text-ink">{rating.value.toFixed(1)}</span>
      <span className="text-ink-muted">
        ({rating.count} {rating.source})
      </span>
    </span>
  );
}
