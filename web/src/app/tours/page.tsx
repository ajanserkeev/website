import type { Metadata } from "next";
import Link from "next/link";
import { Lock } from "lucide-react";
import { ActiveFilters, CatalogFilters, SortSelect } from "@/components/catalog/catalog-filters";
import { TourCard } from "@/components/tour/tour-card";
import { buttonVariants } from "@/components/ui/button";
import {
  activityName,
  difficultyLabels,
  listActivities,
  listRegions,
  listTours,
  regionName,
  toCard,
  upcomingMonths,
} from "@/lib/catalog";
import { durationLabels, loadCatalogParams, type CatalogFilters as Filters } from "@/lib/search-params";

function headline(f: Filters) {
  const what = f.activity ? `${activityName(f.activity)} tours` : "Tours";
  const where = f.region ? ` in ${regionName(f.region)}` : " in Kyrgyzstan";
  return `${what}${where}`;
}

export async function generateMetadata(props: PageProps<"/tours">): Promise<Metadata> {
  const filters = await loadCatalogParams(props.searchParams);
  const results = await listTours(filters);
  return {
    title: headline(filters),
    description: "Multi-day treks, horse riding, yurt stays and day trips from verified local operators.",
    // Empty filter combinations must not be indexed (launch document, section 09).
    robots: results.length ? undefined : { index: false },
  };
}

export default async function ToursPage(props: PageProps<"/tours">) {
  const filters = await loadCatalogParams(props.searchParams);
  const [results, activityList, regionList] = await Promise.all([listTours(filters), listActivities(), listRegions()]);
  const months = upcomingMonths();

  const activeLabels = [
    filters.activity && { key: "activity" as const, label: activityName(filters.activity) },
    filters.region && { key: "region" as const, label: regionName(filters.region) },
    filters.month && { key: "month" as const, label: months.find((m) => m.value === filters.month)?.label ?? filters.month },
    filters.duration && { key: "duration" as const, label: durationLabels[filters.duration] },
    filters.difficulty && { key: "difficulty" as const, label: difficultyLabels[filters.difficulty] },
    filters.maxPrice && { key: "maxPrice" as const, label: `Up to $${filters.maxPrice}` },
  ].filter((x): x is { key: "activity"; label: string } => Boolean(x));

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
      <nav aria-label="Breadcrumb" className="text-sm text-ink-muted">
        <Link href="/" className="hover:text-lake">
          Home
        </Link>{" "}
        / <span className="text-ink">Tours</span>
      </nav>
      <div className="mt-3 flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
          <h1 className="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">{headline(filters)}</h1>
          <p className="mt-1 text-ink-muted">
            {results.length} {results.length === 1 ? "tour" : "tours"} from verified local operators
          </p>
        </div>
        <SortSelect />
      </div>
      <div className="mt-4">
        <ActiveFilters labels={activeLabels} />
      </div>

      <div className="mt-6 grid items-start gap-6 lg:grid-cols-12">
        <aside className="lg:sticky lg:top-24 lg:col-span-3">
          <CatalogFilters
            activities={activityList.map((a) => ({ value: a.slug, label: a.name, count: a.tourCount }))}
            regions={regionList.map((r) => ({ value: r.slug, label: r.name, count: r.tourCount }))}
            months={months}
          />
        </aside>

        <div className="space-y-6 lg:col-span-9">
          {results.length ? (
            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
              {results.map((tour, i) => (
                <TourCard key={tour.slug} tour={toCard(tour)} priority={i < 3} />
              ))}
            </div>
          ) : (
            <div className="rounded-2xl border border-dashed border-line-strong bg-white px-6 py-14 text-center">
              <h2 className="font-display text-xl font-bold text-ink">No tours match these filters</h2>
              <p className="mx-auto mt-2 max-w-md text-ink-muted">
                Try other dates or remove a filter. Or tell us what you are looking for and a local expert will suggest
                a route.
              </p>
              <div className="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <Link href="/tours" className={buttonVariants({ variant: "outline" })}>
                  Show all tours
                </Link>
                <Link href="/plan-my-trip" className={buttonVariants({ variant: "lake" })}>
                  Plan my trip
                </Link>
              </div>
            </div>
          )}

          <div className="flex flex-col items-start gap-4 rounded-2xl bg-lake-50 p-5 sm:flex-row sm:items-center">
            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-meadow text-white">
              <Lock className="size-5" aria-hidden="true" />
            </span>
            <div>
              <h2 className="font-semibold text-ink">No payment before the operator confirms</h2>
              <p className="text-sm text-ink-muted">
                Send a request, we confirm your spots within 24 hours, then you pay a small deposit. The rest goes to
                your operator on day 1.{" "}
                <Link href="/how-it-works" className="font-semibold text-lake hover:underline">
                  How booking works
                </Link>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
