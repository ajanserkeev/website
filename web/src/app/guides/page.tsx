import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { SectionHeading } from "@/components/section-heading";
import { listGuides } from "@/lib/catalog";

export const metadata: Metadata = {
  title: "Kyrgyzstan Travel Guides",
  description: "Practical guides to Kyrgyzstan: when to go, how to get there, where to stay and what to pack.",
};

export default async function GuidesPage() {
  const guides = await listGuides();
  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <SectionHeading
        as="h1"
        eyebrow="Guides"
        title="Kyrgyzstan travel guides"
        text="Written with our local operators. Every fact is checked before we publish."
        className="mb-8"
      />
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {guides.map((g) => (
          <Link
            key={g.slug}
            href={`/guides/${g.slug}`}
            className="group overflow-hidden rounded-2xl border border-line bg-white shadow-card transition-shadow hover:shadow-raised"
          >
            <div className="relative aspect-[16/10] overflow-hidden">
              <Image
                src={g.hero.src}
                alt={g.hero.alt}
                fill
                sizes="(min-width: 1024px) 33vw, 100vw"
                className="object-cover transition-transform duration-500 group-hover:scale-105"
              />
            </div>
            <div className="p-5">
              <p className="text-xs text-ink-muted">{g.readingMinutes} min read</p>
              <h2 className="mt-1 font-semibold text-ink group-hover:text-lake">{g.title}</h2>
              <p className="mt-1 line-clamp-2 text-sm text-ink-muted">{g.excerpt}</p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
