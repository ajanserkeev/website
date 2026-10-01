"use client";

import { ChevronDown, SlidersHorizontal, X } from "lucide-react";
import { useQueryStates } from "nuqs";
import { useState, useTransition } from "react";
import { cn } from "cn";
import {
  catalogParsers,
  difficultyLevels,
  durationBuckets,
  durationLabels,
  sortLabels,
  sortOptions,
  type SortOption,
} from "@/lib/search-params";

type Option = { value: string; label: string; count?: number };

const difficultyLabels = { easy: "Easy", moderate: "Moderate", challenging: "Challenging" } as const;
const priceSteps = [100, 300, 500, 1000];

function useCatalogQuery() {
  const [isPending, startTransition] = useTransition();
  const [query, setQuery] = useQueryStates(catalogParsers, { shallow: false, startTransition, scroll: false });
  return { query, setQuery, isPending };
}

function Chip({ active, onClick, children }: { active: boolean; onClick: () => void; children: React.ReactNode }) {
  return (
    <button
      type="button"
      aria-pressed={active}
      onClick={onClick}
      className={cn(
        "rounded-full border px-3 py-1.5 text-sm font-semibold transition-colors",
        active ? "border-ink bg-ink text-white" : "border-line bg-white text-ink hover:border-line-strong",
      )}
    >
      {children}
    </button>
  );
}

function Group({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <fieldset className="space-y-2.5">
      <legend className="mb-2.5 text-sm font-semibold text-ink">{title}</legend>
      {children}
    </fieldset>
  );
}

function RadioList({
  name,
  options,
  value,
  onChange,
}: {
  name: string;
  options: Option[];
  value: string | null;
  onChange: (v: string | null) => void;
}) {
  return (
    <div className="space-y-1">
      {[{ value: "", label: "All" }, ...options].map((o) => (
        <label
          key={o.value || "all"}
          className="flex cursor-pointer items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm text-ink-muted hover:bg-snow hover:text-ink has-checked:font-semibold has-checked:text-ink"
        >
          <span className="flex items-center gap-2">
            <input
              type="radio"
              name={name}
              checked={(value ?? "") === o.value}
              onChange={() => onChange(o.value || null)}
              className="size-4 accent-lake"
            />
            {o.label}
          </span>
          {o.count !== undefined && <span className="font-mono text-xs text-lake">{o.count}</span>}
        </label>
      ))}
    </div>
  );
}

export function CatalogFilters({
  activities,
  regions,
  months,
}: {
  activities: Option[];
  regions: Option[];
  months: Option[];
}) {
  const { query, setQuery, isPending } = useCatalogQuery();
  const activeCount = [query.activity, query.region, query.month, query.duration, query.difficulty, query.maxPrice].filter(
    Boolean,
  ).length;

  // Collapsed behind a button on phones so results come first; always open on desktop.
  const [open, setOpen] = useState(false);

  return (
    <div className="rounded-2xl border border-line bg-white">
      <div className="flex items-center justify-between p-4">
        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          aria-expanded={open}
          aria-controls="catalog-filters"
          className="flex items-center gap-2 font-semibold text-ink lg:pointer-events-none"
        >
          <SlidersHorizontal className="size-4" aria-hidden="true" />
          Filters {activeCount > 0 && <span className="text-lake">({activeCount})</span>}
          <ChevronDown className={cn("size-4 transition-transform lg:hidden", open && "rotate-180")} aria-hidden="true" />
        </button>
        {activeCount > 0 && (
          <button type="button" onClick={() => setQuery(null)} className="text-sm font-semibold text-lake hover:underline">
            Reset
          </button>
        )}
      </div>
      <div
        id="catalog-filters"
        className={cn("space-y-6 border-t border-line p-4 transition-opacity lg:block", !open && "hidden", isPending && "opacity-60")}
      >
        <Group title="Activity">
          <RadioList name="activity" options={activities} value={query.activity} onChange={(activity) => setQuery({ activity })} />
        </Group>
        <Group title="Region">
          <RadioList name="region" options={regions} value={query.region} onChange={(region) => setQuery({ region })} />
        </Group>
        <Group title="When">
          <select
            value={query.month ?? ""}
            onChange={(e) => setQuery({ month: e.target.value || null })}
            className="h-10 w-full rounded-lg border border-line-strong bg-white px-3 text-sm text-ink"
            aria-label="Month"
          >
            <option value="">Any month</option>
            {months.map((m) => (
              <option key={m.value} value={m.value}>
                {m.label}
              </option>
            ))}
          </select>
        </Group>
        <Group title="Duration">
          <div className="flex flex-wrap gap-2">
            {durationBuckets.map((b) => (
              <Chip key={b} active={query.duration === b} onClick={() => setQuery({ duration: query.duration === b ? null : b })}>
                {durationLabels[b]}
              </Chip>
            ))}
          </div>
        </Group>
        <Group title="Difficulty">
          <div className="flex flex-wrap gap-2">
            {difficultyLevels.map((d) => (
              <Chip
                key={d}
                active={query.difficulty === d}
                onClick={() => setQuery({ difficulty: query.difficulty === d ? null : d })}
              >
                {difficultyLabels[d]}
              </Chip>
            ))}
          </div>
        </Group>
        <Group title="Price per person">
          <div className="flex flex-wrap gap-2">
            {priceSteps.map((p) => (
              <Chip key={p} active={query.maxPrice === p} onClick={() => setQuery({ maxPrice: query.maxPrice === p ? null : p })}>
                Up to ${p.toLocaleString("en-US")}
              </Chip>
            ))}
          </div>
        </Group>
      </div>
    </div>
  );
}

export function SortSelect() {
  const { query, setQuery } = useCatalogQuery();
  return (
    <label className="flex items-center gap-2 text-sm">
      <span className="text-ink-muted">Sort</span>
      <select
        value={query.sort}
        onChange={(e) => setQuery({ sort: e.target.value as SortOption })}
        className="h-10 rounded-lg border border-line-strong bg-white px-3 font-semibold text-ink"
      >
        {sortOptions.map((s) => (
          <option key={s} value={s}>
            {sortLabels[s]}
          </option>
        ))}
      </select>
    </label>
  );
}

export function ActiveFilters({ labels }: { labels: { key: keyof typeof catalogParsers; label: string }[] }) {
  const { setQuery } = useCatalogQuery();
  if (!labels.length) return null;
  return (
    <div className="flex flex-wrap items-center gap-2">
      {labels.map(({ key, label }) => (
        <button
          key={key}
          type="button"
          onClick={() => setQuery({ [key]: null })}
          className="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-sm font-semibold text-ink shadow-card hover:text-kyrgyz"
        >
          {label}
          <X className="size-3.5" aria-label={`Remove ${label}`} />
        </button>
      ))}
      <button type="button" onClick={() => setQuery(null)} className="text-sm font-semibold text-lake hover:underline">
        Clear all
      </button>
    </div>
  );
}
