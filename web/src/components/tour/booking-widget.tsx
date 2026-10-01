"use client";

import { ArrowRight, CalendarCheck, Minus, Plus, RotateCcw, ShieldCheck, Timer } from "lucide-react";
import { useState } from "react";
import { cn } from "cn";
import { Money } from "@/components/money";
import { brand, whatsappUrl } from "@/lib/brand";
import type { Cents, PrivatePrice } from "@/lib/types";
import { PriceLedger } from "./price-ledger";

export type WidgetDeparture = {
  id: string;
  startsOn: string;
  endsOn: string;
  priceCents: Cents;
  seatsLeft: number;
  guaranteed: boolean;
};

type Props = {
  tourTitle: string;
  operatorName: string;
  durationDays: number;
  commissionRate: number;
  groupSizeMin: number;
  groupSizeMax: number;
  minAge?: number;
  departures: WidgetDeparture[];
  privatePrices: PrivatePrice[];
};

const formatRange = (start: string, end: string) => {
  const f = (d: string, opts: Intl.DateTimeFormatOptions) =>
    new Date(`${d}T00:00:00Z`).toLocaleDateString("en-GB", { ...opts, timeZone: "UTC" });
  return start === end
    ? f(start, { day: "numeric", month: "short", year: "numeric" })
    : `${f(start, { day: "numeric", month: "short" })} – ${f(end, { day: "numeric", month: "short", year: "numeric" })}`;
};

function privatePriceFor(prices: PrivatePrice[], people: number): Cents | null {
  return prices.find((p) => people >= p.groupSizeFrom && people <= p.groupSizeTo)?.pricePerPersonCents ?? null;
}

/** Sticky booking widget (launch document, items 10–11). Until the booking API exists (step 4.6),
 * "Check availability" sends a prefilled WhatsApp request. */
export function BookingWidget(props: Props) {
  const hasGroup = props.departures.length > 0;
  const hasPrivate = props.privatePrices.length > 0;
  const firstOpen = props.departures.find((d) => d.seatsLeft > 0);

  const [mode, setMode] = useState<"group" | "private">(hasGroup || !hasPrivate ? "group" : "private");
  const [departureId, setDepartureId] = useState(firstOpen?.id ?? "");
  const [travelers, setTravelers] = useState(Math.max(2, props.groupSizeMin));
  const [privateDate, setPrivateDate] = useState("");
  const [dateError, setDateError] = useState(false);

  const pickDate = (value: string) => {
    const today = new Date().toISOString().slice(0, 10);
    const valid = !value || value > today;
    setDateError(!valid);
    setPrivateDate(valid ? value : "");
  };

  const departure = props.departures.find((d) => d.id === departureId);
  const maxTravelers = mode === "group" ? Math.min(props.groupSizeMax, departure?.seatsLeft ?? props.groupSizeMax) : props.groupSizeMax;
  const people = Math.min(Math.max(travelers, mode === "private" ? props.privatePrices[0]?.groupSizeFrom ?? 1 : 1), maxTravelers);

  const unitCents = mode === "group" ? (departure?.priceCents ?? null) : privatePriceFor(props.privatePrices, people);
  const totalCents = unitCents === null ? null : unitCents * people;
  const prices = [...props.departures.map((d) => d.priceCents), ...props.privatePrices.map((p) => p.pricePerPersonCents)];
  const fromCents = prices.length ? Math.min(...prices) : null;

  const dates =
    mode === "group" ? (departure ? formatRange(departure.startsOn, departure.endsOn) : "") : privateDate || "flexible dates";
  const ready = mode === "group" ? Boolean(departure && departure.seatsLeft > 0) : Boolean(privateDate);
  const message = [
    `Hi ${brand.name}! I'd like to check availability:`,
    `Tour: ${props.tourTitle}`,
    `Type: ${mode === "group" ? "group departure" : "private tour"}`,
    `Dates: ${dates}`,
    `Travelers: ${people}`,
  ].join("\n");

  return (
    <div id="book" className="scroll-mt-24 overflow-hidden rounded-2xl border border-line bg-white shadow-overlay">
      <div className="h-1.5 bg-gradient-to-r from-kyrgyz via-lake to-meadow" aria-hidden="true" />
      <div className="space-y-5 p-5">
        {hasGroup && hasPrivate && (
          <div className="grid grid-cols-2 gap-1 rounded-lg bg-snow p-1" role="tablist" aria-label="Tour type">
            {(["group", "private"] as const).map((m) => (
              <button
                key={m}
                type="button"
                role="tab"
                aria-selected={mode === m}
                onClick={() => setMode(m)}
                className={cn(
                  "rounded-md py-2 text-sm font-semibold transition-colors",
                  mode === m ? "bg-white text-ink shadow-card" : "text-ink-muted hover:text-ink",
                )}
              >
                {m === "group" ? "Group dates" : "Private, any date"}
              </button>
            ))}
          </div>
        )}

        {fromCents !== null ? (
          <p className="text-sm text-ink-muted">
            from <Money cents={fromCents} className="font-display text-2xl font-bold text-ink tabular" /> / person
          </p>
        ) : (
          <p className="text-sm text-ink-muted">Dates and prices on request: ask us and we&apos;ll check with the operator.</p>
        )}

        {mode === "group" ? (
          <fieldset>
            <legend className="mb-2 text-sm font-semibold text-ink">Departure</legend>
            <div className="space-y-2">
              {props.departures.map((d) => {
                const full = d.seatsLeft === 0;
                return (
                  <label
                    key={d.id}
                    className={cn(
                      "flex cursor-pointer items-center justify-between gap-3 rounded-lg border p-3 transition-colors",
                      d.id === departureId ? "border-lake bg-lake-50" : "border-line hover:border-line-strong",
                      full && "cursor-not-allowed opacity-50",
                    )}
                  >
                    <span className="flex items-center gap-2.5">
                      <input
                        type="radio"
                        name="departure"
                        value={d.id}
                        checked={d.id === departureId}
                        disabled={full}
                        onChange={() => setDepartureId(d.id)}
                        className="size-4 accent-lake"
                      />
                      <span>
                        <span className="block text-sm font-semibold text-ink">{formatRange(d.startsOn, d.endsOn)}</span>
                        <span
                          className={cn(
                            "block text-xs font-semibold",
                            full ? "text-ink-muted" : d.seatsLeft <= 3 ? "text-kyrgyz" : d.guaranteed ? "text-meadow" : "text-ink-muted",
                          )}
                        >
                          {full
                            ? "Full"
                            : d.seatsLeft <= 3
                              ? `Only ${d.seatsLeft} ${d.seatsLeft === 1 ? "spot" : "spots"} left`
                              : d.guaranteed
                                ? "Guaranteed departure"
                                : `${d.seatsLeft} spots left`}
                        </span>
                      </span>
                    </span>
                    <Money cents={d.priceCents} className="font-mono text-sm font-semibold text-ink" />
                  </label>
                );
              })}
            </div>
          </fieldset>
        ) : (
          <label className="block">
            <span className="mb-2 block text-sm font-semibold text-ink">Start date</span>
            <input
              type="date"
              value={privateDate}
              onChange={(e) => pickDate(e.target.value)}
              aria-invalid={dateError}
              className="h-11 w-full rounded-lg border border-line-strong px-3 text-ink aria-invalid:border-kyrgyz"
            />
            {dateError && <span className="mt-1 block text-xs text-kyrgyz">Choose a date from tomorrow on.</span>}
          </label>
        )}

        <div className="flex items-center justify-between rounded-lg bg-snow p-3">
          <div>
            <p className="text-sm font-semibold text-ink">Travelers</p>
            <p className="text-xs text-ink-muted">{props.minAge ? `Ages ${props.minAge}+` : "All ages welcome"}</p>
          </div>
          <div className="flex items-center gap-3">
            <button
              type="button"
              aria-label="Fewer travelers"
              onClick={() => setTravelers(Math.max(1, people - 1))}
              disabled={people <= 1}
              className="flex size-9 items-center justify-center rounded-md bg-white text-ink shadow-card disabled:opacity-40"
            >
              <Minus className="size-4" />
            </button>
            <span className="w-5 text-center font-semibold text-ink tabular" aria-live="polite">
              {people}
            </span>
            <button
              type="button"
              aria-label="More travelers"
              onClick={() => setTravelers(Math.min(maxTravelers, people + 1))}
              disabled={people >= maxTravelers}
              className="flex size-9 items-center justify-center rounded-md bg-white text-ink shadow-card disabled:opacity-40"
            >
              <Plus className="size-4" />
            </button>
          </div>
        </div>

        {totalCents !== null && (
          <PriceLedger
            totalCents={totalCents}
            commissionRate={props.commissionRate}
            caption={`${people} ${people === 1 ? "traveler" : "travelers"}${mode === "private" ? ", private tour" : ""}`}
          />
        )}

        <a
          href={ready ? whatsappUrl(message) : undefined}
          target="_blank"
          rel="noopener noreferrer"
          aria-disabled={!ready}
          className={cn(
            "flex h-12 items-center justify-center gap-2 rounded-lg bg-kyrgyz px-6 text-base font-semibold text-white shadow-sm transition-colors hover:bg-kyrgyz-hover",
            !ready && "pointer-events-none opacity-50",
          )}
        >
          Check availability
          <ArrowRight className="size-5" aria-hidden="true" />
        </a>
        {!ready && <p className="-mt-3 text-center text-xs text-ink-muted">Choose {mode === "group" ? "a departure" : "a start date"} first.</p>}

        <ul className="space-y-2 text-sm text-ink-muted">
          <li className="flex gap-2">
            <Timer className="mt-0.5 size-4 shrink-0 text-meadow" aria-hidden="true" />
            No payment now. We confirm with {props.operatorName} within 24 hours.
          </li>
          <li className="flex gap-2">
            <RotateCcw className="mt-0.5 size-4 shrink-0 text-meadow" aria-hidden="true" />
            Full deposit refund if you cancel 30+ days before.
          </li>
          <li className="flex gap-2">
            <ShieldCheck className="mt-0.5 size-4 shrink-0 text-meadow" aria-hidden="true" />
            If the operator cancels, you get 100% back.
          </li>
          <li className="flex gap-2">
            <CalendarCheck className="mt-0.5 size-4 shrink-0 text-meadow" aria-hidden="true" />
            {props.durationDays === 1 ? "Day trip" : `${props.durationDays} days`}, prices in USD.
          </li>
        </ul>
      </div>
    </div>
  );
}
