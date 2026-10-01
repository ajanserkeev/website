import type { Cents } from "./types";

export const currencies = ["USD", "EUR", "GBP", "AUD"] as const;
export type Currency = (typeof currencies)[number];

/**
 * Deposit rounded to whole dollars: round(total × rate / 100), in dollars. "Pay $50 to book" reads better than $49.50.
 * api/app/Services/Booking/DepositCalculator must use the same rule (step 4.6).
 */
export function depositCents(totalCents: Cents, commissionRate: number): Cents {
  return Math.round((totalCents * commissionRate) / 100 / 100) * 100;
}

export function formatMoney(cents: Cents, currency: Currency = "USD", ratePerUsd = 1): string {
  // Converted prices are indicative, so they are rounded to whole units; USD shows cents only when present.
  const amount = currency === "USD" ? cents / 100 : Math.round((cents / 100) * ratePerUsd);
  const digits = Number.isInteger(amount) ? 0 : 2;
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency,
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
  }).format(amount);
}
