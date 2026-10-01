import { Lock, Wallet } from "lucide-react";
import { cn } from "cn";
import { Money } from "@/components/money";
import { depositCents } from "@/lib/money";
import type { Cents } from "@/lib/types";

/** Total · pay now · pay the operator on day 1 (launch document, item 11). */
export function PriceLedger({
  totalCents,
  commissionRate,
  caption,
  className,
}: {
  totalCents: Cents;
  commissionRate: number;
  caption?: string;
  className?: string;
}) {
  const deposit = depositCents(totalCents, commissionRate);
  return (
    <div className={cn("space-y-2.5 rounded-2xl bg-snow p-4 text-sm", className)}>
      {caption && <p className="text-ink-muted">{caption}</p>}
      <div className="flex items-center justify-between gap-3 rounded-lg bg-meadow-50 px-3 py-2 font-semibold text-meadow">
        <span className="flex items-center gap-1.5">
          <Lock className="size-4" aria-hidden="true" />
          Pay now to book ({commissionRate}%)
        </span>
        <Money cents={deposit} className="tabular" />
      </div>
      <div className="flex items-center justify-between gap-3 px-3 text-ink-muted">
        <span className="flex items-center gap-1.5">
          <Wallet className="size-4" aria-hidden="true" />
          Pay the operator on day 1
        </span>
        <Money cents={totalCents - deposit} className="font-semibold text-ink tabular" />
      </div>
      <div className="flex items-center justify-between gap-3 border-t border-line px-3 pt-2.5 text-base font-bold text-ink">
        <span>Total</span>
        <Money cents={totalCents} className="tabular" />
      </div>
    </div>
  );
}
