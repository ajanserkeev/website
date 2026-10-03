import type { Metadata } from "next";
import { ExploreMapPage } from "@/components/map/lazy-maps";
import { getExploreMap } from "@/lib/catalog";

export const metadata: Metadata = {
  title: "Map of Kyrgyzstan: lakes, passes and tour routes",
  description: "Interactive map of Song-Kul, Ala-Kul, Tash-Rabat and other places, with the routes of every tour. Switch to satellite or 3D relief.",
  alternates: { canonical: "/map" },
};

export default async function MapPage(props: PageProps<"/map">) {
  const [data, query] = await Promise.all([getExploreMap(), props.searchParams]);
  const place = typeof query.place === "string" ? query.place : undefined;

  return <ExploreMapPage data={data} initialPlace={place} />;
}
