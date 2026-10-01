"use client";

import { useRates } from "@/components/rates-provider";
import { formatMoney } from "@/lib/money";
import { usePreferences } from "@/lib/preferences";
import type { Cents } from "@/lib/types";

/** Price in the visitor's display currency. Payments are always charged in USD. */
export function Money({ cents, className }: { cents: Cents; className?: string }) {
  const { currency } = usePreferences();
  const rates = useRates();
  const rate = rates[currency];
  const shown = rate ? currency : "USD";
  return (
    <span className={className} title={shown === "USD" ? undefined : `${formatMoney(cents)} · charged in USD`}>
      {formatMoney(cents, shown, rate ?? 1)}
    </span>
  );
}
