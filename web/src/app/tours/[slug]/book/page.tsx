import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { BookingForm } from "@/components/booking/booking-form";
import { getTour } from "@/lib/catalog";
import { getMe } from "@/lib/session";

export const metadata: Metadata = {
  title: "Request to book",
  robots: { index: false },
};

const toInt = (v: string | string[] | undefined, fallback: number) => {
  const n = Number(Array.isArray(v) ? v[0] : v);
  return Number.isInteger(n) && n >= 0 ? n : fallback;
};
const toStr = (v: string | string[] | undefined) => (Array.isArray(v) ? v[0] : v) || undefined;

/** Three steps, no account (launch document, item 17): dates & travelers → details → review & send. */
export default async function BookPage(props: PageProps<"/tours/[slug]/book">) {
  const [{ slug }, query] = await Promise.all([props.params, props.searchParams]);
  const [tour, me] = await Promise.all([getTour(slug), getMe()]);
  if (!tour) notFound();

  return (
    <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
      <nav aria-label="Breadcrumb" className="text-sm text-ink-muted">
        <Link href={`/tours/${tour.slug}`} className="hover:text-lake">
          {tour.title}
        </Link>{" "}
        › <span className="text-ink">Request to book</span>
      </nav>
      <h1 className="mt-2 mb-6 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Request to book</h1>
      <BookingForm
        tour={{
          slug: tour.slug,
          title: tour.title,
          durationDays: tour.durationDays,
          groupSizeMax: tour.groupSizeMax,
          minAge: tour.minAge,
          commissionRate: tour.commissionRate,
          operatorName: tour.operator.name,
          departures: tour.departures,
          privatePrices: tour.privatePrices,
        }}
        initial={{
          departureId: toStr(query.departure),
          date: toStr(query.date),
          adults: Math.max(1, toInt(query.adults, 2)),
          children: toInt(query.children, 0),
          // Signed-in travelers: the booking lands in their account, contact details prefilled.
          customerName: me?.name,
          email: me?.email,
        }}
      />
    </div>
  );
}
