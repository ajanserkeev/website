// Shapes of the public API v1 (api/app/Http/Resources/V1). Money is integer cents, USD; dates are Asia/Bishkek.
// Operator contacts are intentionally absent: they are only revealed in the voucher after deposit_paid.

export type Cents = number;

export type TourType = "multi_day" | "day_trip" | "activity" | "service";

export type Meal = "breakfast" | "lunch" | "dinner";

export interface Photo {
  src: string;
  alt: string;
}

export interface Ref {
  slug: string;
  name: string;
}

export interface Badge {
  kind: "guaranteed" | "spots" | "small-group" | "day-trip" | "private";
  label: string;
}

export interface RatingSummary {
  value: number;
  count: number;
  /** "verified reviews" or "on TripAdvisor" */
  source: string;
}

/** Card in lists: catalog, collections, guides, favorites. */
export interface TourSummary {
  slug: string;
  title: string;
  summary: string;
  type: TourType;
  image: Photo | null;
  durationDays: number;
  regions: Ref[];
  regionNames: string[];
  activities: Ref[];
  difficulty: string;
  difficultyLevel: "easy" | "moderate" | "challenging";
  priceFromCents: Cents | null;
  depositFromCents: Cents | null;
  commissionRate: number;
  rating: RatingSummary | null;
  badges: Badge[];
}

export interface Taxonomy {
  slug: string;
  name: string;
  summary: string | null;
  places?: string[];
  hero: Photo | null;
  tourCount: number;
  metaTitle: string | null;
  metaDescription: string | null;
}

export interface ExternalRating {
  source: "TripAdvisor" | "Google";
  rating: number;
  reviews: number;
  url: string | null;
}

export interface OperatorGuide {
  name: string;
  languages: string[];
  note: string | null;
}

export interface Operator {
  slug: string;
  name: string;
  baseCity: string | null;
  foundedYear: number | null;
  description: string | null;
  commissionRate: number;
  logo: Photo | null;
  ratings: ExternalRating[];
  guides: OperatorGuide[];
}

export interface TourDay {
  day: number;
  title: string;
  description: string;
  overnight: string | null;
  meals: Meal[];
  activityHours: string | null;
  maxAltitudeM: number | null;
}

export interface Departure {
  id: string;
  /** YYYY-MM-DD */
  startsOn: string;
  endsOn: string;
  priceCents: Cents;
  childPriceCents: Cents | null;
  seatsLeft: number;
  status: "open" | "guaranteed" | "full" | "cancelled";
}

export interface PrivatePrice {
  groupSizeFrom: number;
  groupSizeTo: number;
  pricePerPersonCents: Cents;
  childPriceCents: Cents | null;
}

export interface Review {
  id: string;
  author: string;
  country: string | null;
  rating: number;
  body: string;
  /** YYYY-MM */
  tripMonth: string | null;
  verifiedBooking: boolean;
  tour?: { slug: string; title: string };
}

export interface Faq {
  question: string;
  answer: string;
}

/** Everything the tour page shows. */
export interface Tour {
  slug: string;
  title: string;
  summary: string;
  description: string[];
  type: TourType;
  durationDays: number;
  regions: Ref[];
  activities: Ref[];
  /** 1 (easy) – 5 (very challenging) */
  difficulty: number;
  difficultyNote: string | null;
  groupSizeMin: number;
  groupSizeMax: number;
  guideLanguages: string[];
  route: string | null;
  maxAltitudeM: number | null;
  /** Months 1–12, inclusive. */
  season: { from: number; to: number };
  minAge: number | null;
  images: Photo[];
  highlights: string[];
  days: TourDay[];
  included: string[];
  excluded: string[];
  faqs: Faq[];
  operator: Operator;
  commissionRate: number;
  /** Upcoming, not cancelled; full ones have seatsLeft 0. */
  departures: Departure[];
  privatePrices: PrivatePrice[];
  reviews: Review[];
  priceFromCents: Cents | null;
  depositFromCents: Cents | null;
  rating: RatingSummary | null;
  badges: Badge[];
  metaTitle: string | null;
  metaDescription: string | null;
}

export interface Collection {
  slug: string;
  title: string;
  intro: string | null;
  hero: Photo | null;
  tours: TourSummary[];
}

export interface GuideSection {
  id: string;
  title: string;
  paragraphs: string[];
  bullets?: string[];
}

export interface Guide {
  slug: string;
  title: string;
  excerpt: string;
  hero: Photo | null;
  readingMinutes: number;
  /** YYYY-MM-DD */
  updatedOn: string | null;
  facts: { label: string; value: string }[];
  sections?: GuideSection[];
  tours?: TourSummary[];
  metaTitle: string | null;
  metaDescription: string | null;
}

export type CurrencyRates = Record<string, number>;
