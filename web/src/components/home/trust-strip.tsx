import { BadgeCheck, CalendarCheck, MessageCircle, Wallet } from "lucide-react";
import { cn } from "cn";

const items = [
  { Icon: BadgeCheck, title: "Verified local operators", text: "Every operator signs a contract with us", tone: "text-meadow" },
  { Icon: Wallet, title: "Small deposit to book", text: "The rest on arrival, cash or card", tone: "text-lake" },
  { Icon: CalendarCheck, title: "Free cancellation", text: "Full deposit refund 30+ days before", tone: "text-kyrgyz" },
  { Icon: MessageCircle, title: "We reply within 2 hours", text: "WhatsApp, chat or email, 9:00–23:00", tone: "text-meadow" },
];

export function TrustStrip({ className }: { className?: string }) {
  return (
    <ul className={cn("grid gap-4 rounded-2xl border border-line bg-white p-5 sm:grid-cols-2 lg:grid-cols-4", className)}>
      {items.map(({ Icon, title, text, tone }) => (
        <li key={title} className="flex items-center gap-3">
          <span className={cn("flex size-10 shrink-0 items-center justify-center rounded-full bg-snow", tone)}>
            <Icon className="size-5" aria-hidden="true" />
          </span>
          <span>
            <span className="block text-sm font-semibold text-ink">{title}</span>
            <span className="block text-sm text-ink-muted">{text}</span>
          </span>
        </li>
      ))}
    </ul>
  );
}
