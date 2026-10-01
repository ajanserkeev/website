import type { Metadata } from "next";
import { SectionHeading } from "@/components/section-heading";
import { listTours, toCard } from "@/lib/catalog";
import { FavoritesList } from "./favorites-list";

export const metadata: Metadata = {
  title: "Saved Tours",
  robots: { index: false },
};

export default async function FavoritesPage() {
  const tours = (await listTours()).map(toCard);
  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <SectionHeading as="h1" title="Saved tours" text="Saved in this browser only." className="mb-8" />
      <FavoritesList tours={tours} />
    </div>
  );
}
