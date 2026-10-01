import { createLoader, createSerializer, parseAsInteger, parseAsString, parseAsStringLiteral } from "nuqs/server";

export const durationBuckets = ["1", "2-4", "5-8", "9+"] as const;
export type DurationBucket = (typeof durationBuckets)[number];

export const durationLabels: Record<DurationBucket, string> = {
  "1": "1 day",
  "2-4": "2–4 days",
  "5-8": "5–8 days",
  "9+": "9+ days",
};

export const difficultyLevels = ["easy", "moderate", "challenging"] as const;
export type DifficultyLevel = (typeof difficultyLevels)[number];

export const sortOptions = ["recommended", "price-asc", "price-desc", "duration"] as const;
export type SortOption = (typeof sortOptions)[number];

export const sortLabels: Record<SortOption, string> = {
  recommended: "Recommended",
  "price-asc": "Price: low to high",
  "price-desc": "Price: high to low",
  duration: "Shortest first",
};

/** Catalog filters live in the URL so a filtered list can be shared and indexed (launch document, item 05). */
export const catalogParsers = {
  activity: parseAsString,
  region: parseAsString,
  /** YYYY-MM */
  month: parseAsString,
  duration: parseAsStringLiteral(durationBuckets),
  difficulty: parseAsStringLiteral(difficultyLevels),
  /** Whole US dollars per person. */
  maxPrice: parseAsInteger,
  sort: parseAsStringLiteral(sortOptions).withDefault("recommended"),
};

export type CatalogFilters = {
  activity?: string | null;
  region?: string | null;
  month?: string | null;
  duration?: DurationBucket | null;
  difficulty?: DifficultyLevel | null;
  maxPrice?: number | null;
  sort?: SortOption;
};

export const loadCatalogParams = createLoader(catalogParsers);
export const serializeCatalog = createSerializer(catalogParsers);
