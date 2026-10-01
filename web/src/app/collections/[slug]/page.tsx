import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { LandingPage } from "@/components/landing-page";
import { getCollection } from "@/lib/catalog";

export async function generateMetadata(props: PageProps<"/collections/[slug]">): Promise<Metadata> {
  const collection = await getCollection((await props.params).slug);
  if (!collection) return {};
  return {
    title: `${collection.title} in Kyrgyzstan`,
    description: collection.intro ?? undefined,
  };
}

/** Hand-picked list of tours, ordered in the admin (launch document, item 03). */
export default async function CollectionPage(props: PageProps<"/collections/[slug]">) {
  const collection = await getCollection((await props.params).slug);
  if (!collection) notFound();
  const hero = collection.hero ?? collection.tours.find((t) => t.image)?.image ?? null;
  return (
    <LandingPage
      crumbs={[
        { href: "/tours", label: "Tours" },
        { href: `/collections/${collection.slug}`, label: collection.title },
      ]}
      title={`${collection.title} in Kyrgyzstan`}
      intro={collection.intro}
      hero={hero}
      tours={collection.tours}
    />
  );
}
