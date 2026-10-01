import { CalendarCheck, Flame, Sun, UserRound, Users } from "lucide-react";
import { cn } from "cn";
import type { Badge } from "@/lib/catalog";

const styles: Record<Badge["kind"], { className: string; Icon: typeof Sun }> = {
  guaranteed: { className: "bg-meadow text-white", Icon: CalendarCheck },
  spots: { className: "bg-white text-kyrgyz", Icon: Flame },
  "small-group": { className: "bg-lake-50 text-lake", Icon: Users },
  "day-trip": { className: "bg-white text-ink", Icon: Sun },
  private: { className: "bg-white text-ink", Icon: UserRound },
};

export function TourBadge({ badge, className }: { badge: Badge; className?: string }) {
  const { className: tone, Icon } = styles[badge.kind];
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm",
        tone,
        className,
      )}
    >
      <Icon className="size-3.5" aria-hidden="true" />
      {badge.label}
    </span>
  );
}
