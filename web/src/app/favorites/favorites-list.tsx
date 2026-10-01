"use client";

import Link from "next/link";
import { Heart } from "lucide-react";
import { TourCard } from "@/components/tour/tour-card";
import { buttonVariants } from "@/components/ui/button";
import type { TourCardData } from "@/lib/catalog";
import { usePreferences } from "@/lib/preferences";

export function FavoritesList({ tours }: { tours: TourCardData[] }) {
  const { favorites } = usePreferences();
  const saved = favorites.map((slug) => tours.find((t) => t.slug === slug)).filter((t): t is TourCardData => Boolean(t));

  if (!saved.length) {
    return (
      <div className="rounded-2xl border border-dashed border-line-strong bg-white px-6 py-14 text-center">
        <Heart className="mx-auto size-8 text-line-strong" aria-hidden="true" />
        <h2 className="mt-3 font-semibold text-ink">No saved tours yet</h2>
        <p className="mt-1 text-ink-muted">Tap the heart on any tour to compare it here later.</p>
        <Link href="/tours" className={buttonVariants({ variant: "lake", className: "mt-6" })}>
          Browse tours
        </Link>
      </div>
    );
  }

  return (
    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      {saved.map((t) => (
        <TourCard key={t.slug} tour={t} />
      ))}
    </div>
  );
}
