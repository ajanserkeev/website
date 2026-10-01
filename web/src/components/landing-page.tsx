import Image from "next/image";
import Link from "next/link";
import { TourCard } from "@/components/tour/tour-card";
import type { TourCardData } from "@/lib/catalog";
import type { Photo } from "@/lib/types";

/** Region and activity landing pages: hero, intro and the matching tours (launch document, section 09). */
export function LandingPage({
  crumbs,
  title,
  intro,
  hero,
  tours,
  catalogHref,
}: {
  crumbs: { href: string; label: string }[];
  title: string;
  intro: string;
  hero: Photo;
  tours: TourCardData[];
  catalogHref: string;
}) {
  return (
    <>
      <section className="relative isolate overflow-hidden">
        <Image src={hero.src} alt={hero.alt} fill preload sizes="100vw" className="-z-10 object-cover" />
        <div className="absolute inset-0 -z-10 bg-gradient-to-t from-ink/85 via-ink/40 to-ink/10" />
        <div className="mx-auto max-w-7xl px-4 pt-24 pb-10 text-white sm:px-6 sm:pt-32">
          <nav aria-label="Breadcrumb" className="flex gap-1 text-sm text-white/80">
            {crumbs.map((c, i) => (
              <span key={c.href} className="flex gap-1">
                {i > 0 && <span aria-hidden="true">›</span>}
                <Link href={c.href} className="hover:text-white">
                  {c.label}
                </Link>
              </span>
            ))}
          </nav>
          <h1 className="mt-2 font-display text-3xl font-bold tracking-tight sm:text-4xl">{title}</h1>
          <p className="mt-3 max-w-2xl text-lg text-white/90">{intro}</p>
        </div>
      </section>
      <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div className="mb-6 flex items-end justify-between gap-4">
          <h2 className="font-display text-xl font-bold text-ink">
            {tours.length} {tours.length === 1 ? "tour" : "tours"}
          </h2>
          <Link href={catalogHref} className="font-semibold text-lake hover:underline">
            Filter by dates and price →
          </Link>
        </div>
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {tours.map((t) => (
            <TourCard key={t.slug} tour={t} />
          ))}
        </div>
      </section>
    </>
  );
}
