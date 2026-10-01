import type { Metadata } from "next";
import { ShyrdakDivider } from "@/components/brand/shyrdak-divider";
import { TundukMark } from "@/components/brand/tunduk-mark";
import { PriceLedger } from "@/components/tour/price-ledger";
import { TourBadge } from "@/components/tour/tour-badge";
import { TourCard } from "@/components/tour/tour-card";
import { Button } from "@/components/ui/button";
import { listTours } from "@/lib/catalog";

export const metadata: Metadata = { title: "Styleguide", robots: { index: false } };

const colors = [
  { name: "Kyrgyz red", token: "kyrgyz", hex: "#C4122F", use: "The one primary action per screen" },
  { name: "Sun", token: "sun", hex: "#E7A500", use: "Ratings" },
  { name: "Night ink", token: "ink", hex: "#172031", use: "Text, footer" },
  { name: "Lake", token: "lake", hex: "#2D5D8C", use: "Links, secondary actions, maps" },
  { name: "Meadow", token: "meadow", hex: "#11804A", use: "Deposit, success, trust" },
  { name: "Snow", token: "snow", hex: "#F6F7F9", use: "Page background" },
];

const swatch: Record<string, string> = {
  kyrgyz: "bg-kyrgyz",
  sun: "bg-sun",
  ink: "bg-ink",
  lake: "bg-lake",
  meadow: "bg-meadow",
  snow: "bg-snow",
};

/** Design tokens and base components (step 2.2). */
export default async function StyleguidePage() {
  const [tour] = await listTours();
  return (
    <div className="mx-auto max-w-7xl space-y-14 px-4 py-10 sm:px-6">
      <header className="flex items-center gap-4">
        <TundukMark className="size-14 text-kyrgyz" />
        <div>
          <h1 className="font-display text-3xl font-bold text-ink">Styleguide</h1>
          <p className="text-ink-muted">Tokens live in src/app/globals.css.</p>
        </div>
      </header>

      <section>
        <h2 className="mb-4 font-display text-xl font-bold">Colors</h2>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {colors.map((c) => (
            <div key={c.token} className="overflow-hidden rounded-2xl border border-line bg-white">
              <div className={`h-20 ${swatch[c.token]}`} />
              <div className="p-4">
                <p className="font-semibold text-ink">
                  {c.name} <span className="font-mono text-sm text-ink-muted">{c.hex}</span>
                </p>
                <p className="text-sm text-ink-muted">{c.use}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      <section>
        <h2 className="mb-4 font-display text-xl font-bold">Type</h2>
        <div className="space-y-3 rounded-2xl border border-line bg-white p-6">
          <p className="font-display text-4xl font-bold text-ink">Ride to Song-Kul with a local family</p>
          <p className="text-lg text-ink-muted">
            Work Sans for text and interface. Three days on horseback across two mountain passes, nights in a yurt camp by
            the lake.
          </p>
          <p className="font-mono text-ink">TT-27-0142 · 3,016 m · $99.00</p>
        </div>
      </section>

      <section>
        <h2 className="mb-4 font-display text-xl font-bold">Buttons</h2>
        <div className="flex flex-wrap gap-3">
          <Button variant="primary" size="lg">
            Check availability
          </Button>
          <Button variant="lake">View dates</Button>
          <Button variant="meadow">Ask on WhatsApp</Button>
          <Button variant="outline">Show all tours</Button>
          <Button variant="soft">Reset</Button>
          <Button variant="ghost">Cancel</Button>
          <Button variant="link">Read more</Button>
        </div>
      </section>

      <section>
        <h2 className="mb-4 font-display text-xl font-bold">Badges</h2>
        <div className="flex flex-wrap gap-2">
          <TourBadge badge={{ kind: "guaranteed", label: "Guaranteed departure" }} />
          <TourBadge badge={{ kind: "spots", label: "Only 3 spots left" }} />
          <TourBadge badge={{ kind: "small-group", label: "Small group" }} />
          <TourBadge badge={{ kind: "day-trip", label: "Day trip" }} />
          <TourBadge badge={{ kind: "private", label: "Private, any date" }} />
        </div>
      </section>

      <ShyrdakDivider />

      <section className="grid items-start gap-8 lg:grid-cols-2">
        <div>
          <h2 className="mb-4 font-display text-xl font-bold">Tour card</h2>
          {tour && <TourCard tour={tour} />}
        </div>
        <div>
          <h2 className="mb-4 font-display text-xl font-bold">Price ledger</h2>
          <PriceLedger totalCents={66000} commissionRate={15} caption="2 travelers" className="bg-white ring-1 ring-line" />
        </div>
      </section>
    </div>
  );
}
