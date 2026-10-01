import Form from "next/form";
import { ArrowRight, Compass, CalendarDays, Hourglass } from "lucide-react";
import { durationBuckets, durationLabels } from "@/lib/search-params";

type Option = { value: string; label: string };

function Field({
  name,
  label,
  Icon,
  options,
  anyLabel,
}: {
  name: string;
  label: string;
  Icon: typeof Compass;
  options: Option[];
  anyLabel: string;
}) {
  return (
    <label className="flex flex-col rounded-xl bg-snow px-3 py-2 transition-colors focus-within:bg-lake-50 hover:bg-secondary">
      <span className="text-xs font-semibold text-ink-muted">{label}</span>
      <span className="flex items-center gap-2">
        <Icon className="size-5 shrink-0 text-lake" aria-hidden="true" />
        <select name={name} defaultValue="" className="w-full cursor-pointer bg-transparent py-1 text-base font-semibold text-ink outline-none">
          <option value="">{anyLabel}</option>
          {options.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
          ))}
        </select>
      </span>
    </label>
  );
}

/** Three fields: Activity, When, Duration. One country, so no "where" field (launch document, item 01). */
export function HeroSearch({ activities, months }: { activities: Option[]; months: Option[] }) {
  return (
    <Form action="/tours" className="rounded-2xl border border-line bg-white p-3 shadow-overlay sm:p-4">
      <div className="grid gap-2 md:grid-cols-3">
        <Field name="activity" label="Activity" Icon={Compass} options={activities} anyLabel="Any activity" />
        <Field name="month" label="When" Icon={CalendarDays} options={months} anyLabel="Any month" />
        <Field
          name="duration"
          label="Duration"
          Icon={Hourglass}
          options={durationBuckets.map((b) => ({ value: b, label: durationLabels[b] }))}
          anyLabel="Any length"
        />
      </div>
      <button
        type="submit"
        className="mt-3 flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-kyrgyz px-6 text-base font-semibold text-white shadow-sm transition-colors hover:bg-kyrgyz-hover"
      >
        Search tours
        <ArrowRight className="size-5" aria-hidden="true" />
      </button>
    </Form>
  );
}
