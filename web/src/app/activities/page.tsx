import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { SectionHeading } from "@/components/section-heading";
import { listActivities } from "@/lib/catalog";

export const metadata: Metadata = {
  title: "Things to do in Kyrgyzstan",
  description: "Horse riding, trekking, yurt stays, Silk Road culture and mountaineering with local operators.",
};

export default async function ActivitiesPage() {
  const activities = await listActivities();
  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <SectionHeading as="h1" eyebrow="Activities" title="Things to do in Kyrgyzstan" className="mb-8" />
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {activities.map((a) => (
          <Link
            key={a.slug}
            href={`/activities/${a.slug}`}
            className="group overflow-hidden rounded-2xl border border-line bg-white shadow-card transition-shadow hover:shadow-raised"
          >
            <div className="relative aspect-[4/3] overflow-hidden">
              <Image
                src={a.hero.src}
                alt={a.hero.alt}
                fill
                sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw"
                className="object-cover transition-transform duration-500 group-hover:scale-105"
              />
            </div>
            <div className="p-5">
              <h2 className="flex items-center justify-between font-semibold text-ink group-hover:text-lake">
                {a.name}
                <span className="text-sm font-normal text-ink-muted">
                  {a.tourCount} {a.tourCount === 1 ? "tour" : "tours"}
                </span>
              </h2>
              <p className="mt-1 text-sm text-ink-muted">{a.summary}</p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
