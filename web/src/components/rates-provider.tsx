"use client";

import { createContext, useContext } from "react";
import type { CurrencyRates } from "@/lib/types";

const RatesContext = createContext<CurrencyRates>({ USD: 1 });

/** Display rates from GET /currency-rates, loaded once in the root layout. */
export function RatesProvider({ rates, children }: { rates: CurrencyRates; children: React.ReactNode }) {
  return <RatesContext value={rates}>{children}</RatesContext>;
}

export const useRates = () => useContext(RatesContext);
