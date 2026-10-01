"use client";

import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { useState } from "react";
import { cn } from "cn";
import { TourCard } from "@/components/tour/tour-card";
import type { TourCardData } from "@/lib/catalog";

type Collection = { slug: string; title: string; href: string; tours: TourCardData[] };

export function CollectionTabs({ collections }: { collections: Collection[] }) {
  const [active, setActive] = useState(collections[0]?.slug);
  const current = collections.find((c) => c.slug === active) ?? collections[0];
  if (!current) return null;

  return (
    <div>
      <div role="tablist" aria-label="Collections" className="flex gap-1 overflow-x-auto rounded-full bg-white p-1.5 shadow-card sm:inline-flex">
        {collections.map((c) => (
          <button
            key={c.slug}
            role="tab"
            type="button"
            aria-selected={c.slug === current.slug}
            onClick={() => setActive(c.slug)}
            className={cn(
              "shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors",
              c.slug === current.slug ? "bg-ink text-white" : "text-ink-muted hover:bg-secondary hover:text-ink",
            )}
          >
            {c.title}
            <span className="ml-1.5 text-xs opacity-70">{c.tours.length}</span>
          </button>
        ))}
      </div>
      <div role="tabpanel" className="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {current.tours.slice(0, 3).map((tour) => (
          <TourCard key={tour.slug} tour={tour} />
        ))}
      </div>
      <Link href={current.href} className="mt-6 inline-flex items-center gap-1 font-semibold text-lake hover:underline">
        See all in {current.title}
        <ArrowRight className="size-4" aria-hidden="true" />
      </Link>
    </div>
  );
}
