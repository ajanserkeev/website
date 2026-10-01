import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Check, MapPin } from "lucide-react";
import { Money } from "@/components/money";
import { BookingWidget } from "@/components/tour/booking-widget";
import { Rating } from "@/components/tour/rating";
import { TourBadge } from "@/components/tour/tour-badge";
import { TourCard } from "@/components/tour/tour-card";
import {
  BookWithConfidence,
  Facts,
  Faqs,
  Gallery,
  Included,
  Itinerary,
  OperatorCard,
  Panel,
  Reviews,
} from "@/components/tour/tour-sections";
import { brand } from "@/lib/brand";
import {
  activityName,
  commissionRate,
  depositFromCents,
  findOperator,
  getTour,
  listTours,
  listTourSlugs,
  priceFromCents,
  ratingSummary,
  regionName,
  seatsLeft,
  toCard,
  tourBadges,
  upcomingDepartures,
} from "@/lib/catalog";
import type { Tour } from "@/lib/types";

export async function generateStaticParams() {
  return (await listTourSlugs()).map((slug) => ({ slug }));
}

/** "{Tour}: {N}-Day {Activity} Tour in Kyrgyzstan" when it fits in 60 characters with the brand (section 09). */
function seoTitle(tour: Tour) {
  const activity = activityName(tour.activities[0]);
  const long = `${tour.title}: ${tour.durationDays}-Day ${activity} Tour in Kyrgyzstan`;
  return long.length + brand.name.length + 3 <= 60 ? long : tour.title;
}

export async function generateMetadata(props: PageProps<"/tours/[slug]">): Promise<Metadata> {
  const { slug } = await props.params;
  const tour = await getTour(slug);
  if (!tour) return {};
  const rate = commissionRate(tour);
  const description = `${tour.durationDays} ${tour.durationDays === 1 ? "day" : "days"}, ${tour.regions.map(regionName).join(", ")}. From $${priceFromCents(tour) / 100}. Pay ${rate}% to book, free cancellation 30+ days.`;
  return {
    title: seoTitle(tour),
    description,
    alternates: { canonical: `/tours/${tour.slug}` },
    openGraph: { title: tour.title, description, images: [{ url: tour.images[0].src }] },
  };
}

function jsonLd(tour: Tour) {
  const url = `${brand.siteUrl}/tours/${tour.slug}`;
  const ownReviews = tour.reviews;
  return [
    {
      "@context": "https://schema.org",
      "@type": "TouristTrip",
      name: tour.title,
      description: tour.summary,
      url,
      image: tour.images.map((i) => `${brand.siteUrl}${i.src}`),
      itinerary: {
        "@type": "ItemList",
        itemListElement: tour.days.map((d) => ({ "@type": "ListItem", position: d.day, name: d.title })),
      },
      offers: {
        "@type": "Offer",
        price: (priceFromCents(tour) / 100).toFixed(2),
        priceCurrency: "USD",
        availability: "https://schema.org/InStock",
        url,
      },
      provider: { "@type": "Organization", name: findOperator(tour.operator).name },
      // Only our own reviews: Google's rules forbid marking up ratings collected on other sites (section 09).
      ...(ownReviews.length
        ? {
            aggregateRating: {
              "@type": "AggregateRating",
              ratingValue: (ownReviews.reduce((s, r) => s + r.rating, 0) / ownReviews.length).toFixed(1),
              reviewCount: ownReviews.length,
            },
          }
        : {}),
    },
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      itemListElement: [
        { "@type": "ListItem", position: 1, name: "Tours", item: `${brand.siteUrl}/tours` },
        { "@type": "ListItem", position: 2, name: regionName(tour.regions[0]), item: `${brand.siteUrl}/destinations/${tour.regions[0]}` },
        { "@type": "ListItem", position: 3, name: tour.title, item: url },
      ],
    },
    ...(tour.faqs.length
      ? [
          {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            mainEntity: tour.faqs.map((f) => ({
              "@type": "Question",
              name: f.question,
              acceptedAnswer: { "@type": "Answer", text: f.answer },
            })),
          },
        ]
      : []),
  ];
}

export default async function TourPage(props: PageProps<"/tours/[slug]">) {
  const { slug } = await props.params;
  const tour = await getTour(slug);
  if (!tour) notFound();

  const operator = findOperator(tour.operator);
  const badges = tourBadges(tour);
  const similar = (await listTours({ region: tour.regions[0] })).filter((t) => t.slug !== tour.slug).slice(0, 3);
  const departures = upcomingDepartures(tour).map((d) => ({
    id: d.id,
    startsOn: d.startsOn,
    endsOn: d.endsOn,
    priceCents: d.priceCents,
    seatsLeft: d.status === "full" ? 0 : seatsLeft(d),
    guaranteed: d.status === "guaranteed",
  }));

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd(tour)).replace(/</g, "\\u003c") }}
      />
      <div className="mx-auto max-w-7xl px-4 pt-6 pb-28 sm:px-6 lg:pb-16">
        <nav aria-label="Breadcrumb" className="flex flex-wrap gap-1 text-sm text-ink-muted">
          <Link href="/tours" className="hover:text-lake">
            Tours
          </Link>
          <span aria-hidden="true">›</span>
          <Link href={`/destinations/${tour.regions[0]}`} className="hover:text-lake">
            {regionName(tour.regions[0])}
          </Link>
          <span aria-hidden="true">›</span>
          <Link href={`/activities/${tour.activities[0]}`} className="hover:text-lake">
            {activityName(tour.activities[0])}
          </Link>
        </nav>

        <header className="mt-4 mb-6">
          {badges.length > 0 && (
            <div className="mb-3 flex flex-wrap gap-2">
              {badges.map((b) => (
                <TourBadge key={b.kind} badge={b} className="shadow-none ring-1 ring-line" />
              ))}
            </div>
          )}
          <h1 className="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{tour.title}</h1>
          <div className="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-ink-muted">
            <Rating rating={ratingSummary(tour)} />
            <span className="flex items-center gap-1">
              <MapPin className="size-4 text-lake" aria-hidden="true" />
              {tour.route}
            </span>
            <span>
              by <span className="font-semibold text-ink">{operator.name}</span>
            </span>
          </div>
        </header>

        <Gallery images={tour.images} />
        <div className="mt-6">
          <Facts tour={tour} />
        </div>

        <div className="mt-8 grid items-start gap-8 lg:grid-cols-12">
          <div className="min-w-0 space-y-6 lg:col-span-8">
            <Panel title="About this tour">
              <div className="space-y-3 text-ink-muted">
                {tour.description.map((p) => (
                  <p key={p}>{p}</p>
                ))}
              </div>
              <ul className="mt-5 flex flex-wrap gap-2 border-t border-line pt-5">
                {tour.highlights.map((h) => (
                  <li key={h} className="flex items-center gap-1.5 rounded-lg bg-snow px-3 py-1.5 text-sm font-semibold text-ink">
                    <Check className="size-4 text-meadow" aria-hidden="true" />
                    {h}
                  </li>
                ))}
              </ul>
            </Panel>
            <Panel id="itinerary" title="Itinerary">
              <Itinerary days={tour.days} />
            </Panel>
            <Panel title="What's included">
              <Included included={tour.included} excluded={tour.excluded} />
            </Panel>
            <Panel title="Your local operator">
              <OperatorCard operator={operator} />
            </Panel>
            <Panel title="Book with confidence">
              <BookWithConfidence />
              <p className="mt-4 text-sm text-ink-muted">
                <Link href="/cancellation-policy" className="font-semibold text-lake hover:underline">
                  Cancellation policy
                </Link>{" "}
                ·{" "}
                <Link href="/how-it-works" className="font-semibold text-lake hover:underline">
                  How booking works
                </Link>
              </p>
            </Panel>
            {tour.reviews.length > 0 && (
              <Panel title={`Reviews from travellers (${tour.reviews.length})`}>
                <Reviews reviews={tour.reviews} />
              </Panel>
            )}
            {tour.faqs.length > 0 && (
              <Panel title="Frequently asked questions">
                <Faqs faqs={tour.faqs} />
              </Panel>
            )}
          </div>

          <aside className="lg:sticky lg:top-24 lg:col-span-4">
            <BookingWidget
              tourTitle={tour.title}
              operatorName={operator.name}
              durationDays={tour.durationDays}
              commissionRate={commissionRate(tour)}
              groupSizeMin={tour.groupSizeMin}
              groupSizeMax={tour.groupSizeMax}
              minAge={tour.minAge}
              departures={departures}
              privatePrices={tour.privatePrices}
            />
          </aside>
        </div>

        {similar.length > 0 && (
          <section className="mt-14">
            <h2 className="mb-6 font-display text-2xl font-bold text-ink">More in {regionName(tour.regions[0])}</h2>
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              {similar.map((t) => (
                <TourCard key={t.slug} tour={toCard(t)} />
              ))}
            </div>
          </section>
        )}
      </div>

      {/* Mobile booking bar (launch document, item 10) */}
      <div className="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white/95 px-4 py-3 shadow-overlay backdrop-blur lg:hidden">
        <div className="mx-auto flex max-w-7xl items-center justify-between gap-3">
          <div className="text-sm">
            <p className="text-ink-muted">
              from <Money cents={priceFromCents(tour)} className="text-lg font-bold text-ink tabular" /> / person
            </p>
            <p className="font-semibold text-meadow">
              Pay <Money cents={depositFromCents(tour)} /> to book
            </p>
          </div>
          <a
            href="#book"
            className="flex h-11 items-center rounded-lg bg-kyrgyz px-5 text-sm font-semibold text-white hover:bg-kyrgyz-hover"
          >
            Check availability
          </a>
        </div>
      </div>
    </>
  );
}
