import Image from "next/image";
import Link from "next/link";
import { MessageCircle, Star } from "lucide-react";
import { ShyrdakDivider } from "@/components/brand/shyrdak-divider";
import { CollectionTabs } from "@/components/home/collection-tabs";
import { HeroSearch } from "@/components/home/hero-search";
import { HowBookingWorks } from "@/components/home/how-booking-works";
import { TrustStrip } from "@/components/home/trust-strip";
import { RegionGrid } from "@/components/region-grid";
import { SectionHeading } from "@/components/section-heading";
import { buttonVariants } from "@/components/ui/button";
import { brand, whatsappUrl } from "@/lib/brand";
import { listActivities, listCollections, listFeaturedReviews, listRegions, upcomingMonths } from "@/lib/catalog";

export default async function Home() {
  const [activityList, collectionList, regionList, reviews] = await Promise.all([
    listActivities(),
    listCollections({ featured: true }),
    listRegions(),
    listFeaturedReviews(),
  ]);

  const tabs = collectionList
    .filter((c) => c.tours.length > 0)
    .map((c) => ({ slug: c.slug, title: c.title, href: `/collections/${c.slug}`, tours: c.tours }));

  return (
    <>
      <section className="relative overflow-hidden bg-gradient-to-b from-lake-50 via-snow to-snow">
        <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 pt-10 pb-12 sm:px-6 lg:grid-cols-12 lg:pt-16">
          <div className="lg:col-span-7">
            <p className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm font-semibold text-ink shadow-card">
              <span className="size-2 rounded-full bg-meadow" aria-hidden="true" />
              2027 season dates are open
            </p>
            <h1 className="mt-4 font-display text-3xl leading-tight font-bold tracking-tight text-ink sm:text-4xl lg:text-5xl">
              Kyrgyzstan with local experts. Horse treks, yurts and mountain lakes.
            </h1>
            <p className="mt-4 max-w-xl text-lg text-ink-muted">
              Book small-group and private tours directly with verified Kyrgyz operators. Pay a small deposit to book,
              the rest on arrival.
            </p>
            <div className="mt-8">
              <HeroSearch
                activities={activityList.map((a) => ({ value: a.slug, label: a.name }))}
                months={upcomingMonths()}
              />
            </div>
          </div>
          <div className="relative hidden h-[480px] lg:col-span-5 lg:block">
            <div className="absolute top-0 right-0 h-[320px] w-[86%] rotate-1 overflow-hidden rounded-3xl shadow-overlay">
              <Image
                src="/demo/jailoo-caravan.webp"
                alt="Kyrgyz horsemen leading pack horses across a summer pasture towards a yurt camp"
                fill
                preload
                sizes="40vw"
                className="object-cover"
              />
            </div>
            <div className="absolute bottom-0 left-0 h-[250px] w-[78%] -rotate-2 overflow-hidden rounded-3xl border-[6px] border-white shadow-overlay">
              <Image
                src="/demo/song-kul-panorama.webp"
                alt="Yurts and grazing horses on the shore of Song-Kul at sunset"
                fill
                sizes="35vw"
                className="object-cover"
              />
            </div>
          </div>
        </div>
        <div className="mx-auto max-w-7xl px-4 pb-12 sm:px-6">
          <TrustStrip />
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
        <SectionHeading
          eyebrow="Collections"
          title="Find your kind of trip"
          text="Hand-picked tours from operators we work with directly."
          className="mb-6"
        />
        <CollectionTabs collections={tabs} />
      </section>

      <ShyrdakDivider className="mx-auto max-w-4xl px-4" />

      <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
        <div className="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
          <SectionHeading
            eyebrow="Destinations"
            title="Where to go in Kyrgyzstan"
            text="From the high pastures of Naryn to the glaciers above Karakol."
          />
          <Link href="/destinations" className="font-semibold text-lake hover:underline">
            All destinations →
          </Link>
        </div>
        <RegionGrid regions={regionList} />
      </section>

      <section className="bg-white py-16">
        <div className="mx-auto max-w-7xl px-4 sm:px-6">
          <SectionHeading
            eyebrow="No surprises"
            title="How booking works"
            text="You never pay before the operator confirms your spots."
            className="mb-8"
          />
          <HowBookingWorks />
        </div>
      </section>

      {reviews.length > 0 && (
        <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
          <SectionHeading eyebrow="Reviews" title="What travellers say" className="mb-8" />
          <ul className="grid gap-6 md:grid-cols-3">
            {reviews.map((r) => (
              <li key={r.id} className="flex flex-col rounded-2xl border border-line bg-white p-6">
                <div className="flex gap-0.5" aria-label={`${r.rating} out of 5`}>
                  {Array.from({ length: 5 }, (_, i) => (
                    <Star
                      key={i}
                      className={i < r.rating ? "size-4 fill-sun text-sun" : "size-4 text-line-strong"}
                      aria-hidden="true"
                    />
                  ))}
                </div>
                <p className="mt-3 flex-1 text-ink">“{r.body}”</p>
                <div className="mt-4 border-t border-line pt-3 text-sm">
                  <p className="font-semibold text-ink">
                    {r.author}
                    {r.country && `, ${r.country}`}
                  </p>
                  {r.tour && (
                    <Link href={`/tours/${r.tour.slug}`} className="text-ink-muted hover:text-lake">
                      {r.tour.title}
                      {r.verifiedBooking && <span className="ml-1 text-meadow">· Verified booking</span>}
                    </Link>
                  )}
                </div>
              </li>
            ))}
          </ul>
        </section>
      )}

      <section className="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
        <div className="rounded-3xl bg-ink px-6 py-12 text-center text-white sm:px-12">
          <h2 className="font-display text-2xl font-bold sm:text-3xl">Not sure where to start?</h2>
          <p className="mx-auto mt-3 max-w-xl text-slate-300">
            Tell us your dates and what you like. A local expert in Bishkek will suggest a route within 24 hours.
          </p>
          <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <Link href="/plan-my-trip" className={buttonVariants({ variant: "lake", size: "lg" })}>
              Plan my trip
            </Link>
            <a
              href={whatsappUrl(`Hi ${brand.name}! Can you help me plan a trip to Kyrgyzstan?`)}
              target="_blank"
              rel="noopener noreferrer"
              className={buttonVariants({ variant: "outline", size: "lg" })}
            >
              <MessageCircle aria-hidden="true" />
              Ask on WhatsApp
            </a>
          </div>
        </div>
      </section>
    </>
  );
}
