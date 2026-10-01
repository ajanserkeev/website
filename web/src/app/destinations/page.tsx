import type { Metadata } from "next";
import { RegionGrid } from "@/components/region-grid";
import { SectionHeading } from "@/components/section-heading";
import { listRegions } from "@/lib/catalog";

export const metadata: Metadata = {
  title: "Destinations in Kyrgyzstan",
  description: "Naryn and Song-Kul, Issyk-Kul and Karakol, Bishkek and Ala-Archa, Osh and the Alay valley.",
};

export default async function DestinationsPage() {
  const regions = await listRegions();
  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <SectionHeading
        as="h1"
        eyebrow="Destinations"
        title="Where to go in Kyrgyzstan"
        text="Kyrgyzstan is small, but the roads are slow. Most trips combine one or two regions."
        className="mb-8"
      />
      <RegionGrid regions={regions} />
    </div>
  );
}
