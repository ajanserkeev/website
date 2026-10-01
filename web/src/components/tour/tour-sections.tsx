import Image from "next/image";
import {
  BadgeCheck,
  CalendarDays,
  Check,
  ChevronDown,
  Gauge,
  Languages,
  Lock,
  Mountain,
  Star,
  Sun,
  Tent,
  Timer,
  Users,
  Utensils,
  X,
} from "lucide-react";
import { cn } from "cn";
import type { Faq, Operator, Review, Tour, TourDay } from "@/lib/types";

const monthName = (m: number) => new Date(Date.UTC(2000, m - 1, 1)).toLocaleDateString("en-US", { month: "short", timeZone: "UTC" });

export function Gallery({ images }: { images: Tour["images"] }) {
  if (!images.length) return null;
  const [hero, ...rest] = images;
  const thumbs = rest.slice(0, 4);
  return (
    <div className={cn("grid gap-2 overflow-hidden rounded-3xl", thumbs.length ? "md:grid-cols-4 md:grid-rows-2" : "")}>
      <div className={cn("relative aspect-[16/10] md:aspect-auto", thumbs.length ? "md:col-span-2 md:row-span-2 md:min-h-[440px]" : "md:h-[460px]")}>
        <Image src={hero.src} alt={hero.alt} fill preload sizes="(min-width: 768px) 50vw, 100vw" className="object-cover" />
      </div>
      {thumbs.map((img) => (
        <div key={img.src} className="relative hidden md:block">
          <Image src={img.src} alt={img.alt} fill sizes="25vw" className="object-cover" />
        </div>
      ))}
    </div>
  );
}

export function Facts({ tour }: { tour: Tour }) {
  const facts = [
    { Icon: CalendarDays, label: "Duration", value: tour.durationDays === 1 ? "1 day" : `${tour.durationDays} days`, note: tour.durationDays > 1 ? `${tour.durationDays - 1} nights` : "Day trip" },
    { Icon: Gauge, label: "Difficulty", value: ["Easy", "Easy", "Moderate", "Challenging", "Very challenging"][tour.difficulty - 1], note: tour.difficultyNote ?? "" },
    { Icon: Users, label: "Group size", value: `${tour.groupSizeMin}–${tour.groupSizeMax} people`, note: tour.minAge ? `Ages ${tour.minAge}+` : "All ages" },
    { Icon: Languages, label: "Guide", value: tour.guideLanguages.slice(0, 2).join(", "), note: tour.guideLanguages.length > 2 ? `+${tour.guideLanguages.length - 2} more` : "Languages" },
    ...(tour.maxAltitudeM
      ? [{ Icon: Mountain, label: "Max altitude", value: `${tour.maxAltitudeM.toLocaleString("en-US")} m`, note: "Highest point" }]
      : []),
    { Icon: Sun, label: "Season", value: `${monthName(tour.season.from)} – ${monthName(tour.season.to)}`, note: "Best months" },
  ];
  return (
    <dl className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
      {facts.map(({ Icon, label, value, note }) => (
        <div key={label} className="rounded-2xl border border-line bg-white p-4">
          <dt className="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-ink-muted uppercase">
            <Icon className="size-4 text-lake" aria-hidden="true" />
            {label}
          </dt>
          <dd className="mt-1 font-semibold text-ink">{value}</dd>
          <dd className="text-xs text-ink-muted">{note}</dd>
        </div>
      ))}
    </dl>
  );
}

const mealLabel = (meals: TourDay["meals"]) =>
  meals.length === 3 ? "All meals" : meals.map((m) => m[0].toUpperCase() + m.slice(1)).join(", ");

export function Itinerary({ days }: { days: TourDay[] }) {
  return (
    <ol className="relative space-y-3 before:absolute before:top-4 before:bottom-4 before:left-[19px] before:w-0.5 before:bg-line">
      {days.map((day, i) => (
        <li key={day.day} className="relative">
          <details open={i === 0} className="group rounded-2xl pl-14">
            <summary className="flex cursor-pointer list-none items-start justify-between gap-3 py-2">
              <span className="absolute top-1 left-0 flex size-10 items-center justify-center rounded-full border-4 border-white bg-lake-50 font-display text-sm font-bold text-lake">
                {day.day}
              </span>
              <span>
                <span className="block text-xs font-semibold tracking-wide text-ink-muted uppercase">Day {day.day}</span>
                <span className="block font-semibold text-ink group-hover:text-lake">{day.title}</span>
              </span>
              <ChevronDown className="mt-4 size-5 shrink-0 text-ink-muted transition-transform group-open:rotate-180" aria-hidden="true" />
            </summary>
            <div className="pb-3">
              <p className="text-ink-muted">{day.description}</p>
              <ul className="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-ink">
                {day.overnight && (
                  <li className="flex items-center gap-1 rounded-md bg-snow px-2 py-1">
                    <Tent className="size-3.5 text-meadow" aria-hidden="true" />
                    {day.overnight}
                  </li>
                )}
                {day.meals.length > 0 && (
                  <li className="flex items-center gap-1 rounded-md bg-snow px-2 py-1">
                    <Utensils className="size-3.5 text-lake" aria-hidden="true" />
                    {mealLabel(day.meals)}
                  </li>
                )}
                {day.activityHours && (
                  <li className="flex items-center gap-1 rounded-md bg-snow px-2 py-1">
                    <Timer className="size-3.5 text-lake" aria-hidden="true" />
                    {day.activityHours}
                  </li>
                )}
                {day.maxAltitudeM && (
                  <li className="flex items-center gap-1 rounded-md bg-snow px-2 py-1 font-mono">
                    <Mountain className="size-3.5 text-lake" aria-hidden="true" />
                    {day.maxAltitudeM.toLocaleString("en-US")} m
                  </li>
                )}
              </ul>
            </div>
          </details>
        </li>
      ))}
    </ol>
  );
}

export function Included({ included, excluded }: { included: string[]; excluded: string[] }) {
  return (
    <div className="grid gap-6 md:grid-cols-2">
      <div>
        <h3 className="mb-3 font-semibold text-ink">Included</h3>
        <ul className="space-y-2.5">
          {included.map((item) => (
            <li key={item} className="flex gap-2.5 text-ink">
              <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-meadow-50 text-meadow">
                <Check className="size-3.5" aria-hidden="true" />
              </span>
              {item}
            </li>
          ))}
        </ul>
      </div>
      <div>
        <h3 className="mb-3 font-semibold text-ink">Not included</h3>
        <ul className="space-y-2.5">
          {excluded.map((item) => (
            <li key={item} className="flex gap-2.5 text-ink-muted">
              <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-secondary text-ink-muted">
                <X className="size-3.5" aria-hidden="true" />
              </span>
              {item}
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}

export function OperatorCard({ operator }: { operator: Operator }) {
  const initials = operator.name
    .split(" ")
    .slice(0, 2)
    .map((w) => w[0])
    .join("");
  return (
    <div>
      <div className="flex flex-col gap-4 border-b border-line pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-4">
          <span className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-ink font-display font-bold text-white">
            {initials}
          </span>
          <div>
            <p className="flex items-center gap-1.5 font-semibold text-ink">
              {operator.name}
              <BadgeCheck className="size-5 text-meadow" aria-label="Verified operator" />
            </p>
            <p className="text-sm text-ink-muted">
              {[operator.baseCity, operator.foundedYear && `since ${operator.foundedYear}`].filter(Boolean).join(" · ")}
            </p>
          </div>
        </div>
        <div className="flex gap-2">
          {operator.ratings.map((r) => (
            <a
              key={r.source}
              href={r.url ?? undefined}
              target="_blank"
              rel="noopener noreferrer"
              className="rounded-lg bg-snow px-3 py-1.5 text-center hover:bg-secondary"
            >
              <span className="flex items-center justify-center gap-1 font-bold text-ink">
                <Star className="size-3.5 fill-sun text-sun" aria-hidden="true" />
                {r.rating.toFixed(1)}
              </span>
              <span className="block text-xs text-ink-muted">
                {r.source} ({r.reviews})
              </span>
            </a>
          ))}
        </div>
      </div>
      {operator.description && <p className="mt-4 text-ink-muted">{operator.description}</p>}
      <ul className="mt-4 space-y-1.5 text-sm">
        {operator.guides.map((g) => (
          <li key={g.name} className="text-ink">
            <span className="font-semibold">{g.name}</span>{" "}
            <span className="text-ink-muted">
              ({g.languages.join(", ")}){g.note && ` · ${g.note}`}
            </span>
          </li>
        ))}
      </ul>
      <p className="mt-4 flex items-start gap-2 rounded-lg bg-snow p-3 text-sm text-ink-muted">
        <Lock className="mt-0.5 size-4 shrink-0 text-lake" aria-hidden="true" />
        The operator&apos;s phone and WhatsApp are in your voucher, sent right after you pay the deposit.
      </p>
    </div>
  );
}

export function BookWithConfidence() {
  const items = [
    { title: "Small deposit", text: "You pay only the deposit online. The rest goes to the operator on day 1." },
    { title: "Free cancellation", text: "Full deposit refund 30+ days before departure, 50% at 14–29 days." },
    { title: "Operator cancels?", text: "You get 100% back and our help finding a similar tour." },
    { title: "Support in English", text: "We answer on WhatsApp, chat and email within 2 hours, 9:00–23:00." },
  ];
  return (
    <ul className="grid gap-4 sm:grid-cols-2">
      {items.map((item) => (
        <li key={item.title} className="flex gap-3">
          <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-meadow-50 text-meadow">
            <Check className="size-4" aria-hidden="true" />
          </span>
          <span>
            <span className="block font-semibold text-ink">{item.title}</span>
            <span className="block text-sm text-ink-muted">{item.text}</span>
          </span>
        </li>
      ))}
    </ul>
  );
}

export function Reviews({ reviews }: { reviews: Review[] }) {
  return (
    <ul className="divide-y divide-line">
      {reviews.map((r) => (
        <li key={r.id} className="py-4 first:pt-0 last:pb-0">
          <div className="flex items-center justify-between gap-3">
            <p className="font-semibold text-ink">
              {r.author} {r.country && <span className="font-normal text-ink-muted">· {r.country}</span>}
            </p>
            <span className="flex gap-0.5" aria-label={`${r.rating} out of 5`}>
              {Array.from({ length: 5 }, (_, i) => (
                <Star key={i} className={i < r.rating ? "size-4 fill-sun text-sun" : "size-4 text-line-strong"} aria-hidden="true" />
              ))}
            </span>
          </div>
          <p className="text-sm text-ink-muted">
            {r.tripMonth &&
              `Travelled ${new Date(`${r.tripMonth}-01T00:00:00Z`).toLocaleDateString("en-US", { month: "long", year: "numeric", timeZone: "UTC" })}`}
            {r.verifiedBooking && <span className="text-meadow"> · Verified booking</span>}
          </p>
          <p className="mt-2 text-ink">{r.body}</p>
        </li>
      ))}
    </ul>
  );
}

export function Faqs({ faqs }: { faqs: Faq[] }) {
  return (
    <div className="space-y-2">
      {faqs.map((f) => (
        <details key={f.question} className="group rounded-xl bg-snow px-4">
          <summary className="flex cursor-pointer list-none items-center justify-between gap-3 py-3.5 font-semibold text-ink">
            {f.question}
            <ChevronDown className="size-5 shrink-0 text-ink-muted transition-transform group-open:rotate-180" aria-hidden="true" />
          </summary>
          <p className="pb-4 text-ink-muted">{f.answer}</p>
        </details>
      ))}
    </div>
  );
}

export function Panel({ id, title, children }: { id?: string; title: string; children: React.ReactNode }) {
  return (
    <section id={id} className="scroll-mt-24 rounded-2xl border border-line bg-white p-5 sm:p-6">
      <h2 className="mb-5 font-display text-xl font-bold text-ink">{title}</h2>
      {children}
    </section>
  );
}
