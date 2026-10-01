import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { LandingPage } from "@/components/landing-page";
import { getRegion, listRegions, listTours, toCard } from "@/lib/catalog";
import { serializeCatalog } from "@/lib/search-params";

export async function generateStaticParams() {
  return (await listRegions()).map((r) => ({ region: r.slug }));
}

export async function generateMetadata(props: PageProps<"/destinations/[region]">): Promise<Metadata> {
  const region = await getRegion((await props.params).region);
  return region ? { title: `${region.name} Tours`, description: region.summary } : {};
}

export default async function RegionPage(props: PageProps<"/destinations/[region]">) {
  const region = await getRegion((await props.params).region);
  if (!region) notFound();
  const tours = await listTours({ region: region.slug });
  return (
    <LandingPage
      crumbs={[
        { href: "/destinations", label: "Destinations" },
        { href: `/destinations/${region.slug}`, label: region.name },
      ]}
      title={`${region.name} tours`}
      intro={region.summary}
      hero={region.hero}
      tours={tours.map(toCard)}
      catalogHref={`/tours${serializeCatalog({ region: region.slug })}`}
    />
  );
}
