// Catalog queries. They read demo data today; in step 5.2 each function becomes a fetch to /api/v1
// with the same return types, so pages and components don't change.
import { activities, collections, operators, regions, tours } from "@/data/demo/catalog";
import { guides } from "@/data/demo/guides";
import { depositCents } from "./money";
import type { CatalogFilters, DifficultyLevel, DurationBucket } from "./search-params";
import type { Cents, Departure, Operator, Tour } from "./types";

const today = () => new Date().toISOString().slice(0, 10);

export function commissionRate(tour: Tour): number {
  return tour.commissionRate ?? findOperator(tour.operator).commissionRate;
}

export function findOperator(slug: string): Operator {
  const operator = operators.find((o) => o.slug === slug);
  if (!operator) throw new Error(`Unknown operator ${slug}`);
  return operator;
}

/** Departures that can still be requested: in the future, not cancelled. Full ones stay visible but disabled. */
export function upcomingDepartures(tour: Tour): Departure[] {
  const now = today();
  return tour.departures
    .filter((d) => d.startsOn > now && d.status !== "cancelled")
    .sort((a, b) => a.startsOn.localeCompare(b.startsOn));
}

export const seatsLeft = (d: Departure) => Math.max(d.seatsTotal - d.seatsBooked, 0);

export function priceFromCents(tour: Tour): Cents {
  const prices = [
    ...upcomingDepartures(tour)
      .filter((d) => d.status !== "full")
      .map((d) => d.priceCents),
    ...tour.privatePrices.map((p) => p.pricePerPersonCents),
  ];
  return prices.length ? Math.min(...prices) : 0;
}

export function depositFromCents(tour: Tour): Cents {
  return depositCents(priceFromCents(tour), commissionRate(tour));
}

export function difficultyLevel(difficulty: number): DifficultyLevel {
  if (difficulty <= 2) return "easy";
  if (difficulty === 3) return "moderate";
  return "challenging";
}

export const difficultyLabels: Record<DifficultyLevel, string> = {
  easy: "Easy",
  moderate: "Moderate",
  challenging: "Challenging",
};

export type Badge = { kind: "guaranteed" | "spots" | "small-group" | "day-trip" | "private"; label: string };

/** Badges are derived only from real departure data (launch document, item 07: "honest badges"). */
export function tourBadges(tour: Tour): Badge[] {
  const badges: Badge[] = [];
  const upcoming = upcomingDepartures(tour).filter((d) => d.status !== "full");
  if (upcoming.some((d) => d.status === "guaranteed")) {
    badges.push({ kind: "guaranteed", label: "Guaranteed departure" });
  }
  const scarce = upcoming.map(seatsLeft).filter((n) => n > 0 && n <= 3);
  if (scarce.length) {
    const n = Math.min(...scarce);
    badges.push({ kind: "spots", label: `Only ${n} ${n === 1 ? "spot" : "spots"} left` });
  }
  if (tour.type === "day_trip") badges.push({ kind: "day-trip", label: "Day trip" });
  if (tour.groupSizeMax <= 8 && upcoming.length) badges.push({ kind: "small-group", label: "Small group" });
  if (!tour.departures.length && tour.privatePrices.length) badges.push({ kind: "private", label: "Private, any date" });
  return badges;
}

export type RatingSummary = { value: number; count: number; source: string };

/** Own reviews win once there are enough of them; otherwise show the operator's external rating with its source. */
export function ratingSummary(tour: Tour): RatingSummary | null {
  if (tour.reviews.length >= 3) {
    const value = tour.reviews.reduce((sum, r) => sum + r.rating, 0) / tour.reviews.length;
    return { value: Math.round(value * 10) / 10, count: tour.reviews.length, source: "verified reviews" };
  }
  const best = [...findOperator(tour.operator).ratings].sort((a, b) => b.reviews - a.reviews)[0];
  return best ? { value: best.rating, count: best.reviews, source: `on ${best.source}` } : null;
}

function matchesDuration(days: number, bucket: DurationBucket): boolean {
  switch (bucket) {
    case "1":
      return days === 1;
    case "2-4":
      return days >= 2 && days <= 4;
    case "5-8":
      return days >= 5 && days <= 8;
    case "9+":
      return days >= 9;
  }
}

/** A tour fits a month if it has a group departure then, or runs privately and the month is in its season. */
function matchesMonth(tour: Tour, month: string): boolean {
  if (upcomingDepartures(tour).some((d) => d.startsOn.startsWith(month) && d.status !== "full")) return true;
  const m = Number(month.slice(5, 7));
  const { from, to } = tour.season;
  const inSeason = from <= to ? m >= from && m <= to : m >= from || m <= to;
  return tour.privatePrices.length > 0 && inSeason;
}

export async function listTours(filters: CatalogFilters = {}): Promise<Tour[]> {
  const result = tours.filter((tour) => {
    if (filters.activity && !tour.activities.includes(filters.activity)) return false;
    if (filters.region && !tour.regions.includes(filters.region)) return false;
    if (filters.duration && !matchesDuration(tour.durationDays, filters.duration)) return false;
    if (filters.difficulty && difficultyLevel(tour.difficulty) !== filters.difficulty) return false;
    if (filters.maxPrice && priceFromCents(tour) > filters.maxPrice * 100) return false;
    if (filters.month && !matchesMonth(tour, filters.month)) return false;
    return true;
  });

  switch (filters.sort ?? "recommended") {
    case "price-asc":
      return result.sort((a, b) => priceFromCents(a) - priceFromCents(b));
    case "price-desc":
      return result.sort((a, b) => priceFromCents(b) - priceFromCents(a));
    case "duration":
      return result.sort((a, b) => a.durationDays - b.durationDays);
    default:
      return result.sort((a, b) => b.sortWeight - a.sortWeight);
  }
}

export async function getTour(slug: string) {
  return tours.find((t) => t.slug === slug) ?? null;
}

export async function listTourSlugs() {
  return tours.map((t) => t.slug);
}

export async function listRegions() {
  return regions.map((region) => ({
    ...region,
    tourCount: tours.filter((t) => t.regions.includes(region.slug)).length,
  }));
}

export async function getRegion(slug: string) {
  return regions.find((r) => r.slug === slug) ?? null;
}

export async function listActivities() {
  return activities.map((activity) => ({
    ...activity,
    tourCount: tours.filter((t) => t.activities.includes(activity.slug)).length,
  }));
}

export async function getActivity(slug: string) {
  return activities.find((a) => a.slug === slug) ?? null;
}

export async function listCollections() {
  return Promise.all(
    collections.map(async (c) => ({ slug: c.slug, title: c.title, filter: c.filter, tours: await listTours(c.filter) })),
  );
}

/** Latest site reviews with their tour, for the home page. */
export async function listFeaturedReviews(limit = 3) {
  return tours
    .flatMap((t) => t.reviews.map((review) => ({ ...review, tourTitle: t.title, tourSlug: t.slug })))
    .sort((a, b) => b.tripMonth.localeCompare(a.tripMonth))
    .slice(0, limit);
}

export async function listGuides() {
  return guides;
}

export async function getGuide(slug: string) {
  return guides.find((g) => g.slug === slug) ?? null;
}

export async function toursBySlugs(slugs: string[]) {
  return slugs.map((s) => tours.find((t) => t.slug === s)).filter((t): t is Tour => Boolean(t));
}

export function regionName(slug: string) {
  return regions.find((r) => r.slug === slug)?.name ?? slug;
}

export function activityName(slug: string) {
  return activities.find((a) => a.slug === slug)?.name ?? slug;
}

export type TourCardData = {
  slug: string;
  title: string;
  image: Tour["images"][number];
  durationDays: number;
  regionNames: string[];
  difficulty: string;
  priceFromCents: Cents;
  depositFromCents: Cents;
  commissionRate: number;
  rating: RatingSummary | null;
  badges: Badge[];
};

/** Serializable card model, safe to pass from server pages into client components. */
export function toCard(tour: Tour): TourCardData {
  return {
    slug: tour.slug,
    title: tour.title,
    image: tour.images[0],
    durationDays: tour.durationDays,
    regionNames: tour.regions.map(regionName),
    difficulty: difficultyLabels[difficultyLevel(tour.difficulty)],
    priceFromCents: priceFromCents(tour),
    depositFromCents: depositFromCents(tour),
    commissionRate: commissionRate(tour),
    rating: ratingSummary(tour),
    badges: tourBadges(tour),
  };
}

/** "When" options for search: the next 12 months. */
export function upcomingMonths(count = 12): { value: string; label: string }[] {
  const start = new Date();
  start.setUTCDate(1);
  return Array.from({ length: count }, (_, i) => {
    const d = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth() + i + 1, 1));
    return {
      value: d.toISOString().slice(0, 7),
      label: d.toLocaleDateString("en-US", { month: "long", year: "numeric", timeZone: "UTC" }),
    };
  });
}
