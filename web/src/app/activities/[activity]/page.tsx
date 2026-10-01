import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { LandingPage } from "@/components/landing-page";
import { getActivity, listActivities, listTours, toCard } from "@/lib/catalog";
import { serializeCatalog } from "@/lib/search-params";

export async function generateStaticParams() {
  return (await listActivities()).map((a) => ({ activity: a.slug }));
}

export async function generateMetadata(props: PageProps<"/activities/[activity]">): Promise<Metadata> {
  const activity = await getActivity((await props.params).activity);
  return activity ? { title: `${activity.name} tours in Kyrgyzstan`, description: activity.summary } : {};
}

export default async function ActivityPage(props: PageProps<"/activities/[activity]">) {
  const activity = await getActivity((await props.params).activity);
  if (!activity) notFound();
  const tours = await listTours({ activity: activity.slug });
  return (
    <LandingPage
      crumbs={[
        { href: "/activities", label: "Activities" },
        { href: `/activities/${activity.slug}`, label: activity.name },
      ]}
      title={`${activity.name} in Kyrgyzstan`}
      intro={activity.summary}
      hero={activity.hero}
      tours={tours.map(toCard)}
      catalogHref={`/tours${serializeCatalog({ activity: activity.slug })}`}
    />
  );
}
