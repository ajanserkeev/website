import Image from "next/image";
import Link from "next/link";
import { cn } from "cn";
import type { Region } from "@/lib/types";

type RegionWithCount = Region & { tourCount: number };

/** Bento grid of regions with tour counts (launch document, item 04). */
export function RegionGrid({ regions }: { regions: RegionWithCount[] }) {
  return (
    <div className="grid gap-6 md:grid-cols-12">
      {regions.map((region, i) => (
        <Link
          key={region.slug}
          href={`/destinations/${region.slug}`}
          className={cn(
            "group overflow-hidden rounded-2xl border border-line bg-white shadow-card transition-shadow hover:shadow-raised",
            i % 4 === 0 || i % 4 === 3 ? "md:col-span-7" : "md:col-span-5",
          )}
        >
          <div className="relative h-56 overflow-hidden sm:h-64">
            <Image
              src={region.hero.src}
              alt={region.hero.alt}
              fill
              sizes="(min-width: 768px) 60vw, 100vw"
              className="object-cover transition-transform duration-700 group-hover:scale-105"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/10 to-transparent" />
            <div className="absolute inset-x-4 bottom-4 flex items-end justify-between gap-3 text-white">
              <h3 className="font-display text-xl font-bold">{region.name}</h3>
              <span className="shrink-0 rounded-full bg-white px-3 py-1 text-sm font-semibold text-ink">
                {region.tourCount} {region.tourCount === 1 ? "tour" : "tours"}
              </span>
            </div>
          </div>
          <div className="p-5">
            <p className="text-sm text-ink-muted">{region.summary}</p>
            <ul className="mt-3 flex flex-wrap gap-1.5">
              {region.places.map((place) => (
                <li key={place} className="rounded bg-secondary px-2 py-0.5 text-xs font-semibold text-ink">
                  {place}
                </li>
              ))}
            </ul>
          </div>
        </Link>
      ))}
    </div>
  );
}
