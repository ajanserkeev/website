"use client";

import { currencyRates } from "@/data/demo/catalog";
import { formatMoney } from "@/lib/money";
import { usePreferences } from "@/lib/preferences";
import type { Cents } from "@/lib/types";

/** Price in the visitor's display currency. Payments are always charged in USD. */
export function Money({ cents, className }: { cents: Cents; className?: string }) {
  const { currency } = usePreferences();
  return (
    <span className={className} title={currency === "USD" ? undefined : `${formatMoney(cents)} · charged in USD`}>
      {formatMoney(cents, currency, currencyRates[currency])}
    </span>
  );
}
