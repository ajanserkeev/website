import "server-only";
// Catalog queries against the Laravel API v1. Business rules (price from, deposit, badges, rating, filters)
// live in api/app/Services/Catalog; the site only displays them.
import { apiGet } from "./api";
import type { CatalogFilters } from "./search-params";
import type { Collection, CurrencyRates, Guide, Review, Taxonomy, Tour, TourSummary } from "./types";

export type { Badge, RatingSummary } from "./types";
/** Cards are served ready-made by the API. */
export type TourCardData = TourSummary;

export async function listTours(filters: CatalogFilters & { slugs?: string[] } = {}): Promise<TourSummary[]> {
  return (await apiGet<TourSummary[]>("tours", { ...filters })) ?? [];
}

export async function getTour(slug: string) {
  return apiGet<Tour>(`tours/${encodeURIComponent(slug)}`);
}

export async function listRegions() {
  return (await apiGet<Taxonomy[]>("regions")) ?? [];
}

export async function getRegion(slug: string) {
  return apiGet<Taxonomy>(`regions/${encodeURIComponent(slug)}`);
}

export async function listActivities() {
  return (await apiGet<Taxonomy[]>("activities")) ?? [];
}

export async function getActivity(slug: string) {
  return apiGet<Taxonomy>(`activities/${encodeURIComponent(slug)}`);
}

export async function listCollections({ featured = false } = {}) {
  return (await apiGet<Collection[]>("collections", { featured: featured ? 1 : null })) ?? [];
}

export async function getCollection(slug: string) {
  return apiGet<Collection>(`collections/${encodeURIComponent(slug)}`);
}

export async function listGuides() {
  return (await apiGet<Guide[]>("posts")) ?? [];
}

export async function getGuide(slug: string) {
  return apiGet<Guide>(`posts/${encodeURIComponent(slug)}`);
}

export async function listFeaturedReviews(limit = 3) {
  return (await apiGet<Review[]>("reviews/featured", { limit })) ?? [];
}

export async function getCurrencyRates(): Promise<CurrencyRates> {
  try {
    return (await apiGet<CurrencyRates>("currency-rates")) ?? { USD: 1 };
  } catch {
    // The switcher falls back to USD only; prices are always charged in USD anyway.
    return { USD: 1 };
  }
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

export const difficultyLabels = { easy: "Easy", moderate: "Moderate", challenging: "Challenging" } as const;
