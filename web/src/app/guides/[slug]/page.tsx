import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowRight, Clock, MessageCircle } from "lucide-react";
import { TourCard } from "@/components/tour/tour-card";
import { buttonVariants } from "@/components/ui/button";
import { brand, whatsappUrl } from "@/lib/brand";
import { getGuide, listGuides, toCard, toursBySlugs } from "@/lib/catalog";

export async function generateStaticParams() {
  return (await listGuides()).map((g) => ({ slug: g.slug }));
}

export async function generateMetadata(props: PageProps<"/guides/[slug]">): Promise<Metadata> {
  const guide = await getGuide((await props.params).slug);
  if (!guide) return {};
  return {
    title: guide.title,
    description: guide.excerpt,
    alternates: { canonical: `/guides/${guide.slug}` },
    openGraph: { type: "article", images: [{ url: guide.hero.src }] },
  };
}

export default async function GuidePage(props: PageProps<"/guides/[slug]">) {
  const guide = await getGuide((await props.params).slug);
  if (!guide) notFound();
  const tours = (await toursBySlugs(guide.tourSlugs)).map(toCard);
  // Tour cards go after the second section, where the reader starts planning (launch document, item 28).
  const cardsAfter = Math.min(2, guide.sections.length) - 1;
  const updated = new Date(`${guide.updatedOn}T00:00:00Z`).toLocaleDateString("en-US", {
    month: "long",
    year: "numeric",
    timeZone: "UTC",
  });

  const articleLd = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: guide.title,
    description: guide.excerpt,
    image: [`${brand.siteUrl}${guide.hero.src}`],
    dateModified: guide.updatedOn,
    publisher: { "@type": "Organization", name: brand.name },
  };

  return (
    <article>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd).replace(/</g, "\\u003c") }} />
      <header className="bg-white">
        <div className="mx-auto max-w-7xl px-4 pt-8 pb-8 sm:px-6">
          <nav aria-label="Breadcrumb" className="text-sm text-ink-muted">
            <Link href="/guides" className="hover:text-lake">
              Guides
            </Link>
          </nav>
          <h1 className="mt-3 max-w-4xl font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{guide.title}</h1>
          <p className="mt-4 max-w-3xl text-lg text-ink-muted">{guide.excerpt}</p>
          <p className="mt-4 flex items-center gap-2 text-sm text-ink-muted">
            <Clock className="size-4" aria-hidden="true" />
            {guide.readingMinutes} min read · Updated {updated}
          </p>
        </div>
      </header>

      <div className="mx-auto max-w-7xl px-4 sm:px-6">
        <div className="relative aspect-[21/9] min-h-64 overflow-hidden rounded-3xl">
          <Image src={guide.hero.src} alt={guide.hero.alt} fill preload sizes="(min-width: 1280px) 1280px, 100vw" className="object-cover" />
        </div>
      </div>

      <div className="mx-auto grid max-w-7xl items-start gap-10 px-4 py-12 sm:px-6 lg:grid-cols-12">
        <aside className="space-y-6 lg:sticky lg:top-24 lg:col-span-4">
          <nav aria-label="In this guide" className="rounded-2xl border border-line bg-white p-5">
            <h2 className="mb-3 font-semibold text-ink">In this guide</h2>
            <ol className="space-y-1 text-sm">
              {guide.sections.map((s, i) => (
                <li key={s.id}>
                  <a href={`#${s.id}`} className="flex gap-3 rounded-lg p-2 text-ink-muted hover:bg-snow hover:text-ink">
                    <span className="font-mono text-lake">{String(i + 1).padStart(2, "0")}</span>
                    {s.title}
                  </a>
                </li>
              ))}
            </ol>
          </nav>
          <dl className="grid grid-cols-2 gap-3">
            {guide.facts.map((f) => (
              <div key={f.label} className="rounded-xl border border-line bg-white p-3">
                <dt className="text-xs text-ink-muted">{f.label}</dt>
                <dd className="font-mono text-sm font-semibold text-ink">{f.value}</dd>
              </div>
            ))}
          </dl>
          <div className="rounded-2xl bg-meadow-50 p-5">
            <h2 className="font-semibold text-ink">Questions about this trip?</h2>
            <p className="mt-1 text-sm text-ink-muted">Our team in Bishkek answers within 2 hours, 9:00–23:00.</p>
            <a
              href={whatsappUrl(`Hi ${brand.name}! I read your guide "${guide.title}" and have a question.`)}
              target="_blank"
              rel="noopener noreferrer"
              className={buttonVariants({ variant: "meadow", className: "mt-4 w-full" })}
            >
              <MessageCircle aria-hidden="true" />
              Ask on WhatsApp
            </a>
          </div>
        </aside>

        <div className="space-y-10 lg:col-span-8">
          {guide.sections.map((s, i) => (
            <div key={s.id}>
              <section id={s.id} className="scroll-mt-24">
                <p className="font-mono text-sm font-semibold text-lake">{String(i + 1).padStart(2, "0")}</p>
                <h2 className="mt-1 font-display text-2xl font-bold text-ink">{s.title}</h2>
                <div className="mt-4 space-y-4 text-lg leading-relaxed text-ink-muted">
                  {s.paragraphs.map((p) => (
                    <p key={p}>{p}</p>
                  ))}
                </div>
                {s.bullets && (
                  <ul className="mt-4 space-y-2 text-lg text-ink-muted">
                    {s.bullets.map((b) => (
                      <li key={b} className="flex gap-3">
                        <span className="mt-3 size-1.5 shrink-0 rounded-full bg-lake" aria-hidden="true" />
                        {b}
                      </li>
                    ))}
                  </ul>
                )}
              </section>
              {i === cardsAfter && tours.length > 0 && (
                <aside className="mt-10 rounded-3xl bg-white p-5 shadow-card ring-1 ring-line sm:p-6">
                  <h2 className="mb-4 font-semibold text-ink">Tours that go there</h2>
                  <div className="grid gap-5 sm:grid-cols-2">
                    {tours.map((t) => (
                      <TourCard key={t.slug} tour={t} />
                    ))}
                  </div>
                </aside>
              )}
            </div>
          ))}

          <div className="rounded-3xl bg-ink p-8 text-white">
            <h2 className="font-display text-2xl font-bold">Want a custom route?</h2>
            <p className="mt-2 text-slate-300">
              Tell us your dates and pace. A local expert will send you a plan within 24 hours.
            </p>
            <Link href="/plan-my-trip" className={buttonVariants({ variant: "primary", size: "lg", className: "mt-6" })}>
              Plan my trip
              <ArrowRight aria-hidden="true" />
            </Link>
          </div>
        </div>
      </div>
    </article>
  );
}
