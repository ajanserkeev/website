"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { ArrowLeft, ArrowRight, Check, Loader2, Minus, Plus } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMemo, useState } from "react";
import { Controller, useForm, useWatch } from "react-hook-form";
import { z } from "zod";
import { cn } from "cn";
import { PriceLedger } from "@/components/tour/price-ledger";
import { readUtm } from "@/components/utm-capture";
import { brand, whatsappUrl } from "@/lib/brand";
import { countryOptions } from "@/lib/countries";
import type { Departure, PrivatePrice } from "@/lib/types";

export type BookingFormTour = {
  slug: string;
  title: string;
  durationDays: number;
  groupSizeMax: number;
  minAge: number | null;
  commissionRate: number;
  operatorName: string;
  departures: Departure[];
  privatePrices: PrivatePrice[];
};

const schema = z
  .object({
    mode: z.enum(["group", "private"]),
    departureId: z.string(),
    date: z.string(),
    adults: z.number().int().min(1).max(30),
    children: z.number().int().min(0).max(20),
    travelers: z.array(z.string().max(120)),
    specialRequests: z.string().max(2000),
    customerName: z.string().trim().min(2, "Enter your full name").max(120),
    email: z.email("Enter a valid email"),
    whatsapp: z.string().trim().max(40),
    country: z.string(),
    terms: z.boolean().refine((v) => v, "Please accept the booking terms"),
  })
  .superRefine((v, ctx) => {
    if (v.mode === "group" && !v.departureId) ctx.addIssue({ code: "custom", path: ["departureId"], message: "Choose a departure" });
    if (v.mode === "private" && !v.date) ctx.addIssue({ code: "custom", path: ["date"], message: "Choose a start date" });
  });

type Values = z.infer<typeof schema>;

const STEPS = ["Dates & travelers", "Your details", "Review & send"] as const;
const STEP_FIELDS: (keyof Values)[][] = [
  ["mode", "departureId", "date", "adults", "children"],
  ["customerName", "email", "whatsapp", "country"],
  ["terms"],
];
/** Server field names → form field names. */
const SERVER_FIELDS: Record<string, keyof Values> = {
  departure_id: "departureId",
  date_from: "date",
  adults: "adults",
  children: "children",
  customer_name: "customerName",
  email: "email",
  whatsapp: "whatsapp",
  country: "country",
  terms_accepted: "terms",
};

const formatRange = (start: string, end: string) => {
  const f = (d: string, o: Intl.DateTimeFormatOptions) => new Date(`${d}T00:00:00Z`).toLocaleDateString("en-GB", { ...o, timeZone: "UTC" });
  return start === end
    ? f(start, { day: "numeric", month: "short", year: "numeric" })
    : `${f(start, { day: "numeric", month: "short" })} – ${f(end, { day: "numeric", month: "short", year: "numeric" })}`;
};

function Counter({ label, hint, value, min, max, onChange }: { label: string; hint?: string; value: number; min: number; max: number; onChange: (n: number) => void }) {
  return (
    <div className="flex items-center justify-between rounded-xl border border-line bg-white p-3">
      <div>
        <p className="font-semibold text-ink">{label}</p>
        {hint && <p className="text-xs text-ink-muted">{hint}</p>}
      </div>
      <div className="flex items-center gap-3">
        <button type="button" aria-label={`Fewer ${label.toLowerCase()}`} disabled={value <= min} onClick={() => onChange(value - 1)} className="flex size-9 items-center justify-center rounded-md bg-snow text-ink disabled:opacity-40">
          <Minus className="size-4" />
        </button>
        <span className="w-5 text-center font-semibold tabular" aria-live="polite">{value}</span>
        <button type="button" aria-label={`More ${label.toLowerCase()}`} disabled={value >= max} onClick={() => onChange(value + 1)} className="flex size-9 items-center justify-center rounded-md bg-snow text-ink disabled:opacity-40">
          <Plus className="size-4" />
        </button>
      </div>
    </div>
  );
}

function FieldError({ message }: { message?: string }) {
  return message ? <p className="mt-1 text-sm text-kyrgyz">{message}</p> : null;
}

const inputClass = "h-11 w-full rounded-lg border border-line-strong bg-white px-3 text-ink aria-invalid:border-kyrgyz";

export function BookingForm({ tour, initial }: { tour: BookingFormTour; initial: { departureId?: string; date?: string; adults: number; children: number } }) {
  const router = useRouter();
  const [step, setStep] = useState(0);
  const [serverError, setServerError] = useState<string | null>(null);
  const countries = useMemo(() => countryOptions(), []);
  const hasGroup = tour.departures.some((d) => d.seatsLeft > 0);
  const hasPrivate = tour.privatePrices.length > 0;

  const {
    control,
    register,
    handleSubmit,
    trigger,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: {
      mode: initial.date || !hasGroup ? "private" : "group",
      departureId: initial.departureId ?? tour.departures.find((d) => d.seatsLeft > 0)?.id ?? "",
      date: initial.date ?? "",
      adults: initial.adults,
      children: initial.children,
      travelers: [],
      specialRequests: "",
      customerName: "",
      email: "",
      whatsapp: "",
      country: "",
      terms: false,
    },
  });
  const v = useWatch({ control });

  const departure = tour.departures.find((d) => d.id === v.departureId);
  const people = (v.adults ?? 1) + (v.children ?? 0);
  const bracket = tour.privatePrices.find((p) => people >= p.groupSizeFrom && people <= p.groupSizeTo);
  const unit = v.mode === "group" ? departure?.priceCents : bracket?.pricePerPersonCents;
  const childUnit = v.mode === "group" ? (departure?.childPriceCents ?? unit) : (bracket?.childPriceCents ?? unit);
  const total = unit === undefined ? null : (v.adults ?? 1) * unit + (v.children ?? 0) * (childUnit ?? unit);
  const maxPeople = v.mode === "group" ? Math.min(tour.groupSizeMax, departure?.seatsLeft ?? tour.groupSizeMax) : Math.max(...tour.privatePrices.map((p) => p.groupSizeTo), 1);
  const endDate = (start: string) => {
    const d = new Date(`${start}T00:00:00Z`);
    d.setUTCDate(d.getUTCDate() + tour.durationDays - 1);
    return d.toISOString().slice(0, 10);
  };
  const dates = v.mode === "group" ? (departure ? formatRange(departure.startsOn, departure.endsOn) : "") : v.date ? formatRange(v.date, endDate(v.date)) : "";

  const next = async () => {
    if (await trigger(STEP_FIELDS[step])) {
      if (step === 0 && v.mode === "private" && !bracket) {
        setError("adults", { message: `A private tour for ${people} travelers isn't listed. Ask us on WhatsApp.` });
        return;
      }
      setStep(step + 1);
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  };

  const onSubmit = handleSubmit(async (values) => {
    setServerError(null);
    const response = await fetch("/api/bookings", {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({
        tour: tour.slug,
        departure_id: values.mode === "group" ? Number(values.departureId) : null,
        date_from: values.mode === "private" ? values.date : null,
        adults: values.adults,
        children: values.children,
        travelers: values.travelers.filter(Boolean),
        special_requests: values.specialRequests || null,
        customer_name: values.customerName,
        email: values.email,
        whatsapp: values.whatsapp || null,
        country: values.country || null,
        terms_accepted: values.terms,
        utm: readUtm(),
      }),
    });

    if (response.status === 201) {
      const { data } = (await response.json()) as { data: { token: string } };
      router.push(`/booking/${data.token}?new=1`);
      return;
    }
    if (response.status === 422) {
      const body = (await response.json()) as { message?: string; errors?: Record<string, string[]> };
      let firstStep = 2;
      for (const [key, messages] of Object.entries(body.errors ?? {})) {
        const field = SERVER_FIELDS[key];
        if (!field) continue;
        setError(field, { message: messages[0] });
        firstStep = Math.min(firstStep, STEP_FIELDS.findIndex((f) => f.includes(field)));
      }
      setStep(firstStep);
      setServerError(body.message ?? "Please check the highlighted fields.");
      return;
    }
    setServerError(
      response.status === 429
        ? "Too many requests from your connection. Please message us on WhatsApp and we'll book it for you."
        : "Something went wrong on our side. Please try again or message us on WhatsApp.",
    );
  });

  return (
    <form onSubmit={onSubmit} noValidate className="grid items-start gap-8 lg:grid-cols-12">
      <div className="space-y-6 lg:col-span-7">
        <ol className="flex gap-2" aria-label="Steps">
          {STEPS.map((label, i) => (
            <li key={label} className="flex-1">
              <span className={cn("block h-1.5 rounded-full", i <= step ? "bg-lake" : "bg-line")} />
              <span className={cn("mt-2 block text-xs font-semibold", i === step ? "text-ink" : "text-ink-muted")}>
                Step {i + 1}. {label}
              </span>
            </li>
          ))}
        </ol>

        {serverError && <p className="rounded-xl bg-kyrgyz-50 p-4 text-sm text-kyrgyz" role="alert">{serverError}</p>}

        {step === 0 && (
          <section className="space-y-5 rounded-2xl border border-line bg-white p-5">
            {hasGroup && hasPrivate && (
              <Controller
                control={control}
                name="mode"
                render={({ field }) => (
                  <div className="grid grid-cols-2 gap-1 rounded-lg bg-snow p-1" role="radiogroup" aria-label="Tour type">
                    {(["group", "private"] as const).map((m) => (
                      <button key={m} type="button" role="radio" aria-checked={field.value === m} onClick={() => field.onChange(m)} className={cn("rounded-md py-2 text-sm font-semibold", field.value === m ? "bg-white text-ink shadow-card" : "text-ink-muted")}>
                        {m === "group" ? "Group dates" : "Private, any date"}
                      </button>
                    ))}
                  </div>
                )}
              />
            )}

            {v.mode === "group" ? (
              <fieldset>
                <legend className="mb-2 font-semibold text-ink">Departure</legend>
                <div className="space-y-2">
                  {tour.departures.map((d) => (
                    <label key={d.id} className={cn("flex cursor-pointer items-center justify-between gap-3 rounded-lg border p-3", v.departureId === d.id ? "border-lake bg-lake-50" : "border-line", d.seatsLeft === 0 && "cursor-not-allowed opacity-50")}>
                      <span className="flex items-center gap-2.5">
                        <input type="radio" value={d.id} disabled={d.seatsLeft === 0} {...register("departureId")} className="size-4 accent-lake" />
                        <span className="text-sm font-semibold text-ink">{formatRange(d.startsOn, d.endsOn)}</span>
                      </span>
                      <span className="text-xs text-ink-muted">{d.seatsLeft === 0 ? "Full" : `${d.seatsLeft} spots left`}</span>
                    </label>
                  ))}
                </div>
                <FieldError message={errors.departureId?.message} />
              </fieldset>
            ) : (
              <label className="block">
                <span className="mb-2 block font-semibold text-ink">Start date</span>
                <input type="date" {...register("date")} aria-invalid={Boolean(errors.date)} className={inputClass} />
                <span className="mt-1 block text-xs text-ink-muted">{tour.durationDays > 1 ? `${tour.durationDays} days from this date.` : "Day trip."}</span>
                <FieldError message={errors.date?.message} />
              </label>
            )}

            <div className="space-y-2">
              <Controller control={control} name="adults" render={({ field }) => (
                <Counter label="Adults" hint={tour.minAge ? `Ages ${tour.minAge}+` : undefined} value={field.value} min={1} max={maxPeople - (v.children ?? 0)} onChange={field.onChange} />
              )} />
              {!tour.minAge || tour.minAge < 12 ? (
                <Controller control={control} name="children" render={({ field }) => (
                  <Counter label="Children" hint="Under 12" value={field.value} min={0} max={maxPeople - (v.adults ?? 1)} onChange={field.onChange} />
                )} />
              ) : null}
              <FieldError message={errors.adults?.message} />
            </div>

            <label className="block">
              <span className="mb-2 block font-semibold text-ink">Anything we should know?</span>
              <textarea {...register("specialRequests")} rows={3} placeholder="Vegetarian, first time on a horse, arriving the night before…" className="w-full rounded-lg border border-line-strong p-3 text-ink" />
            </label>
          </section>
        )}

        {step === 1 && (
          <section className="space-y-4 rounded-2xl border border-line bg-white p-5">
            <label className="block">
              <span className="mb-1.5 block font-semibold text-ink">Full name</span>
              <input autoComplete="name" {...register("customerName")} aria-invalid={Boolean(errors.customerName)} className={inputClass} />
              <FieldError message={errors.customerName?.message} />
            </label>
            <label className="block">
              <span className="mb-1.5 block font-semibold text-ink">Email</span>
              <input type="email" autoComplete="email" {...register("email")} aria-invalid={Boolean(errors.email)} className={inputClass} />
              <span className="mt-1 block text-xs text-ink-muted">We send the confirmation and the payment link here.</span>
              <FieldError message={errors.email?.message} />
            </label>
            <div className="grid gap-4 sm:grid-cols-2">
              <label className="block">
                <span className="mb-1.5 block font-semibold text-ink">WhatsApp <span className="font-normal text-ink-muted">(optional)</span></span>
                <input type="tel" autoComplete="tel" placeholder="+49 151 1234567" {...register("whatsapp")} className={inputClass} />
              </label>
              <label className="block">
                <span className="mb-1.5 block font-semibold text-ink">Country <span className="font-normal text-ink-muted">(optional)</span></span>
                <select {...register("country")} className={inputClass}>
                  <option value="">Choose…</option>
                  {countries.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                </select>
              </label>
            </div>
            {people > 1 && (
              <fieldset>
                <legend className="mb-1.5 font-semibold text-ink">Names of the other travelers <span className="font-normal text-ink-muted">(optional)</span></legend>
                <div className="grid gap-2 sm:grid-cols-2">
                  {Array.from({ length: people - 1 }, (_, i) => (
                    <input key={i} aria-label={`Traveler ${i + 2}`} placeholder={`Traveler ${i + 2}`} {...register(`travelers.${i}` as const)} className={inputClass} />
                  ))}
                </div>
              </fieldset>
            )}
          </section>
        )}

        {step === 2 && (
          <section className="space-y-4 rounded-2xl border border-line bg-white p-5">
            <dl className="grid gap-3 text-sm sm:grid-cols-2">
              <div><dt className="text-ink-muted">Tour</dt><dd className="font-semibold text-ink">{tour.title}</dd></div>
              <div><dt className="text-ink-muted">Dates</dt><dd className="font-semibold text-ink">{dates}{v.mode === "private" && " · private"}</dd></div>
              <div><dt className="text-ink-muted">Travelers</dt><dd className="font-semibold text-ink">{v.adults} {v.adults === 1 ? "adult" : "adults"}{v.children ? `, ${v.children} ${v.children === 1 ? "child" : "children"}` : ""}</dd></div>
              <div><dt className="text-ink-muted">Contact</dt><dd className="font-semibold text-ink">{v.customerName} · {v.email}</dd></div>
            </dl>
            <label className="flex items-start gap-3 rounded-xl bg-snow p-4 text-sm text-ink">
              <input type="checkbox" {...register("terms")} className="mt-0.5 size-4 accent-lake" />
              <span>
                I agree to the booking terms: no payment until the operator confirms; then a deposit to book, the rest to the operator on day 1; full deposit refund 30+ days before departure. See{" "}
                <Link href="/cancellation-policy" target="_blank" className="font-semibold text-lake underline">cancellation policy</Link> and{" "}
                <Link href="/how-it-works" target="_blank" className="font-semibold text-lake underline">how booking works</Link>.
              </span>
            </label>
            <FieldError message={errors.terms?.message} />
          </section>
        )}

        <div className="flex items-center justify-between gap-3">
          {step > 0 ? (
            <button type="button" onClick={() => setStep(step - 1)} className="flex h-11 items-center gap-1.5 rounded-lg px-3 font-semibold text-ink-muted hover:bg-secondary hover:text-ink">
              <ArrowLeft className="size-4" /> Back
            </button>
          ) : (
            <Link href={`/tours/${tour.slug}`} className="flex h-11 items-center gap-1.5 rounded-lg px-3 font-semibold text-ink-muted hover:bg-secondary">
              <ArrowLeft className="size-4" /> Tour page
            </Link>
          )}
          {step < 2 ? (
            <button type="button" onClick={next} className="flex h-12 items-center gap-2 rounded-lg bg-lake px-6 font-semibold text-white hover:bg-lake-hover">
              Continue <ArrowRight className="size-4" />
            </button>
          ) : (
            <button type="submit" disabled={isSubmitting} className="flex h-12 items-center gap-2 rounded-lg bg-kyrgyz px-6 font-semibold text-white hover:bg-kyrgyz-hover disabled:opacity-60">
              {isSubmitting ? <Loader2 className="size-4 animate-spin" /> : <Check className="size-4" />}
              Send request
            </button>
          )}
        </div>
      </div>

      <aside className="space-y-4 lg:sticky lg:top-24 lg:col-span-5">
        <div className="rounded-2xl border border-line bg-white p-5">
          <p className="font-display text-lg font-bold text-ink">{tour.title}</p>
          <p className="mt-1 text-sm text-ink-muted">{dates || "Choose your dates"} · with {tour.operatorName}</p>
          {total !== null && (
            <PriceLedger totalCents={total} commissionRate={tour.commissionRate} caption={`${people} ${people === 1 ? "traveler" : "travelers"}`} className="mt-4" />
          )}
          <ul className="mt-4 space-y-1.5 text-sm text-ink-muted">
            <li>✓ No payment now. We confirm within 24 hours.</li>
            <li>✓ The final price is checked by our system when you send the request.</li>
          </ul>
        </div>
        <a href={whatsappUrl(`Hi ${brand.name}! I have a question about ${tour.title}.`)} target="_blank" rel="noopener noreferrer" className="block text-center text-sm font-semibold text-meadow hover:underline">
          Questions first? Ask us on WhatsApp
        </a>
      </aside>
    </form>
  );
}
