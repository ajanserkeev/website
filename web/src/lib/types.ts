// Shapes of the public API v1 resources (docs/PLAN.md, data model). Money is integer cents, USD.
// Operator contacts are intentionally absent: they are only revealed in the voucher after deposit_paid.

export type Cents = number;

export type TourType = "multi_day" | "day_trip" | "activity" | "service";

export type Meal = "breakfast" | "lunch" | "dinner";

export interface Photo {
  src: string;
  alt: string;
}

export interface Region {
  slug: string;
  name: string;
  summary: string;
  hero: Photo;
  places: string[];
}

export interface Activity {
  slug: string;
  name: string;
  summary: string;
  hero: Photo;
}

export interface ExternalRating {
  source: "TripAdvisor" | "Google";
  rating: number;
  reviews: number;
  url?: string;
}

export interface OperatorGuide {
  name: string;
  languages: string[];
  note: string;
}

export interface Operator {
  slug: string;
  name: string;
  baseCity: string;
  foundedYear: number;
  description: string;
  commissionRate: number;
  ratings: ExternalRating[];
  guides: OperatorGuide[];
}

export interface TourDay {
  day: number;
  title: string;
  description: string;
  overnight?: string;
  meals: Meal[];
  activityHours?: string;
  maxAltitudeM?: number;
}

export interface Departure {
  id: string;
  /** ISO dates (YYYY-MM-DD), Asia/Bishkek. */
  startsOn: string;
  endsOn: string;
  priceCents: Cents;
  seatsTotal: number;
  seatsBooked: number;
  status: "open" | "guaranteed" | "full" | "cancelled";
}

export interface PrivatePrice {
  groupSizeFrom: number;
  groupSizeTo: number;
  pricePerPersonCents: Cents;
}

export interface Review {
  id: string;
  author: string;
  country: string;
  rating: number;
  body: string;
  /** YYYY-MM */
  tripMonth: string;
  verifiedBooking: boolean;
}

export interface Faq {
  question: string;
  answer: string;
}

export interface Tour {
  slug: string;
  title: string;
  summary: string;
  description: string[];
  type: TourType;
  durationDays: number;
  regions: string[];
  activities: string[];
  /** 1 (easy) – 5 (very challenging) */
  difficulty: number;
  difficultyNote: string;
  groupSizeMin: number;
  groupSizeMax: number;
  guideLanguages: string[];
  route: string;
  maxAltitudeM?: number;
  /** Months 1–12, inclusive. */
  season: { from: number; to: number };
  minAge?: number;
  images: Photo[];
  highlights: string[];
  days: TourDay[];
  included: string[];
  excluded: string[];
  faqs: Faq[];
  operator: string;
  /** Overrides the operator's commission when set. */
  commissionRate?: number;
  departures: Departure[];
  privatePrices: PrivatePrice[];
  reviews: Review[];
  sortWeight: number;
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
  hero: Photo;
  readingMinutes: number;
  /** YYYY-MM-DD */
  updatedOn: string;
  facts: { label: string; value: string }[];
  sections: GuideSection[];
  /** Tours shown as cards inside the article. */
  tourSlugs: string[];
}
