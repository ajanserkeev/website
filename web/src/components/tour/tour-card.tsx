import Image from "next/image";
import Link from "next/link";
import { Clock } from "lucide-react";
import { FavoriteButton } from "@/components/favorite-button";
import { Money } from "@/components/money";
import type { TourCardData } from "@/lib/catalog";
import { Rating } from "./rating";
import { TourBadge } from "./tour-badge";

export function TourCard({ tour, priority = false }: { tour: TourCardData; priority?: boolean }) {
  const badge = tour.badges[0];
  return (
    <article className="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-card transition-shadow hover:shadow-raised">
      <div className="relative aspect-[4/3] overflow-hidden bg-secondary">
        <Image
          src={tour.image.src}
          alt={tour.image.alt}
          fill
          sizes="(min-width: 1280px) 400px, (min-width: 768px) 50vw, 100vw"
          preload={priority}
          className="object-cover transition-transform duration-500 group-hover:scale-105"
        />
        {badge && <TourBadge badge={badge} className="absolute top-3 left-3" />}
        <FavoriteButton slug={tour.slug} title={tour.title} className="absolute top-3 right-3 z-10" />
      </div>
      <div className="flex flex-1 flex-col gap-2 p-4">
        <div className="flex items-center justify-between gap-2 text-xs text-ink-muted">
          <span className="flex items-center gap-1">
            <Clock className="size-3.5" aria-hidden="true" />
            {tour.durationDays} {tour.durationDays === 1 ? "day" : "days"} · {tour.regionNames.join(", ")}
          </span>
          <span className="rounded bg-secondary px-2 py-0.5 font-semibold text-ink">{tour.difficulty}</span>
        </div>
        <h3 className="text-lg leading-snug font-semibold text-ink">
          <Link href={`/tours/${tour.slug}`} className="after:absolute after:inset-0 group-hover:text-lake">
            {tour.title}
          </Link>
        </h3>
        <Rating rating={tour.rating} />
        <div className="mt-auto flex items-end justify-between gap-2 border-t border-line pt-3">
          <p className="text-sm text-ink-muted">
            from{" "}
            <Money cents={tour.priceFromCents} className="text-xl font-bold text-ink tabular" />
            <span className="text-xs"> / person</span>
          </p>
          <span className="rounded-md bg-meadow-50 px-2 py-1 text-xs font-semibold text-meadow">
            Pay <Money cents={tour.depositFromCents} /> to book
          </span>
        </div>
      </div>
    </article>
  );
}
