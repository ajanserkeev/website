import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { CalendarDays, Check, Clock, CreditCard, MessageCircle, Phone, Users } from "lucide-react";
import { cn } from "cn";
import { CancelBooking } from "@/components/booking/cancel-booking";
import { Money } from "@/components/money";
import { PriceLedger } from "@/components/tour/price-ledger";
import { apiGetPrivate } from "@/lib/api";
import { brand, whatsappUrl } from "@/lib/brand";
import type { BookingStatus, MyBooking } from "@/lib/types";

// The link is a secret: never indexed, never cached (launch document, section 07).
export const metadata: Metadata = {
  title: "My booking",
  robots: { index: false, follow: false },
  referrer: "no-referrer",
};

const STEPS = [
  { title: "Request sent", text: "We check spots with the operator, usually within a few hours (max 24 h)." },
  { title: "Spots confirmed", text: "You get an email with a secure payment link, valid for 48 hours." },
  { title: "Deposit paid", text: "Your voucher with the operator's contacts arrives right away." },
  { title: "Your trip", text: "Pay the rest to the operator on day 1." },
] as const;

const STEP_OF: Partial<Record<BookingStatus, number>> = {
  new: 0,
  checking: 0,
  awaiting_payment: 1,
  deposit_paid: 2,
  voucher_sent: 2,
  completed: 3,
};

const CLOSED: BookingStatus[] = ["declined", "expired", "cancelled_by_tourist", "cancelled_by_operator"];

const fmtDate = (d: string) => new Date(`${d}T00:00:00Z`).toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric", timeZone: "UTC" });
const fmtDateTime = (iso: string) =>
  new Date(iso).toLocaleString("en-GB", { day: "numeric", month: "long", hour: "2-digit", minute: "2-digit", timeZone: "Asia/Bishkek" });

export default async function MyBookingPage(props: PageProps<"/booking/[token]">) {
  const [{ token }, query] = await Promise.all([props.params, props.searchParams]);
  const booking = await apiGetPrivate<MyBooking>(`bookings/${encodeURIComponent(token)}`);
  if (!booking) notFound();

  const closed = CLOSED.includes(booking.status);
  const current = STEP_OF[booking.status] ?? 0;
  const paid = Boolean(booking.paidAt);
  const justSent = query.new === "1" && booking.status === "new";
  const contacts = booking.operator.contacts;

  return (
    <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
      {justSent && (
        <div className="mb-6 flex items-start gap-3 rounded-2xl bg-meadow-50 p-5" role="status">
          <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-meadow text-white">
            <Check className="size-5" aria-hidden="true" />
          </span>
          <div>
            <p className="font-semibold text-ink">Request sent · {booking.code}</p>
            <p className="text-sm text-ink-muted">
              We&apos;ve emailed you a copy with a link to this page. Bookmark it to check the status at any time.
            </p>
          </div>
        </div>
      )}

      <p className="font-mono text-sm text-ink-muted">Booking {booking.code}</p>
      <h1 className="mt-1 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">{booking.tour.title}</h1>
      <p className={cn("mt-2 inline-flex rounded-full px-3 py-1 text-sm font-semibold", closed ? "bg-secondary text-ink-muted" : "bg-lake-50 text-lake")}>
        {booking.statusLabel}
      </p>

      <div className="mt-8 grid items-start gap-8 lg:grid-cols-12">
        <div className="space-y-6 lg:col-span-7">
          {!closed && (
            <ol className="space-y-4 rounded-2xl border border-line bg-white p-5" aria-label="Progress">
              {STEPS.map((s, i) => (
                <li key={s.title} className="flex gap-3">
                  <span
                    className={cn(
                      "flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold",
                      i < current || (i === current && paid && i === 2) ? "bg-meadow text-white" : i === current ? "bg-lake text-white" : "bg-secondary text-ink-muted",
                    )}
                  >
                    {i < current ? <Check className="size-4" aria-hidden="true" /> : i + 1}
                  </span>
                  <span>
                    <span className={cn("block font-semibold", i <= current ? "text-ink" : "text-ink-muted")}>{s.title}</span>
                    <span className="block text-sm text-ink-muted">{s.text}</span>
                  </span>
                </li>
              ))}
            </ol>
          )}

          {booking.payment && (
            <section className="rounded-2xl border-2 border-meadow bg-white p-5">
              <h2 className="font-display text-lg font-bold text-ink">Your spots are confirmed</h2>
              <p className="mt-1 text-sm text-ink-muted">
                Pay the deposit to secure them. The link is valid until {fmtDateTime(booking.payment.expiresAt)} (Bishkek time).
              </p>
              <a
                href={booking.payment.url}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-4 flex h-12 items-center justify-center gap-2 rounded-lg bg-kyrgyz px-6 font-semibold text-white hover:bg-kyrgyz-hover"
              >
                <CreditCard className="size-5" aria-hidden="true" />
                Pay <Money cents={booking.depositCents} /> securely
              </a>
            </section>
          )}

          {contacts && (
            <section className="rounded-2xl border border-line bg-white p-5">
              <h2 className="font-display text-lg font-bold text-ink">Your voucher</h2>
              <p className="mt-1 text-sm text-ink-muted">
                Your operator is <span className="font-semibold text-ink">{booking.operator.name}</span>
                {booking.operator.baseCity && ` in ${booking.operator.baseCity}`}. They will message you with the meeting point and time.
              </p>
              {!contacts.whatsapp && !contacts.phone && !contacts.email && (
                <p className="mt-4 text-sm text-ink-muted">We&apos;ll email you the operator&apos;s direct contacts shortly.</p>
              )}
              <ul className="mt-4 space-y-2 text-sm">
                {contacts.contactName && <li className="flex items-center gap-2"><Users className="size-4 text-lake" aria-hidden="true" />{contacts.contactName}</li>}
                {contacts.whatsapp && (
                  <li className="flex items-center gap-2">
                    <MessageCircle className="size-4 text-meadow" aria-hidden="true" />
                    <a href={`https://wa.me/${contacts.whatsapp.replace(/\D/g, "")}`} className="font-semibold text-lake hover:underline">{contacts.whatsapp}</a>
                  </li>
                )}
                {contacts.phone && <li className="flex items-center gap-2"><Phone className="size-4 text-lake" aria-hidden="true" /><a href={`tel:${contacts.phone}`} className="hover:underline">{contacts.phone}</a></li>}
                {contacts.email && <li className="flex items-center gap-2"><span className="text-lake">@</span><a href={`mailto:${contacts.email}`} className="hover:underline">{contacts.email}</a></li>}
              </ul>
              <p className="mt-4 rounded-lg bg-snow p-3 text-sm font-semibold text-ink">
                Pay the operator <Money cents={booking.balanceCents} /> on day 1.
              </p>
            </section>
          )}

          {closed && (
            <section className="rounded-2xl border border-line bg-white p-5 text-sm text-ink-muted">
              <p>
                {booking.status === "declined" && "The operator can't take your group on these dates. You haven't paid anything. "}
                {booking.status === "expired" && "The payment link expired and the spots were released. "}
                {booking.status === "cancelled_by_tourist" && "This booking is cancelled. "}
                {booking.status === "cancelled_by_operator" && "The operator cancelled this tour. Your deposit will be refunded in full. "}
                Want to try other dates or a similar tour?
              </p>
              <a
                href={whatsappUrl(`Hi ${brand.name}! About booking ${booking.code}: can you suggest other dates or a similar tour?`)}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-3 inline-flex items-center gap-1.5 font-semibold text-meadow hover:underline"
              >
                <MessageCircle className="size-4" aria-hidden="true" /> Ask us on WhatsApp
              </a>
            </section>
          )}

          {booking.canCancel && (
            <CancelBooking token={token} refundCents={booking.refundIfCancelledCents} paid={paid} />
          )}
        </div>

        <aside className="space-y-4 lg:col-span-5">
          <div className="overflow-hidden rounded-2xl border border-line bg-white">
            {booking.tour.image && (
              <div className="relative aspect-[16/9]">
                <Image src={booking.tour.image.src} alt={booking.tour.image.alt} fill sizes="(min-width: 1024px) 400px, 100vw" className="object-cover" />
              </div>
            )}
            <div className="space-y-3 p-5 text-sm">
              <Link href={`/tours/${booking.tour.slug}`} className="font-semibold text-ink hover:text-lake">
                {booking.tour.title}
              </Link>
              <p className="flex items-center gap-2 text-ink-muted">
                <CalendarDays className="size-4 text-lake" aria-hidden="true" />
                {booking.dateFrom === booking.dateTo ? fmtDate(booking.dateFrom) : `${fmtDate(booking.dateFrom)} – ${fmtDate(booking.dateTo)}`}
                {booking.pricingSource === "private" && " · private"}
              </p>
              <p className="flex items-center gap-2 text-ink-muted">
                <Users className="size-4 text-lake" aria-hidden="true" />
                {booking.adults} {booking.adults === 1 ? "adult" : "adults"}
                {booking.children > 0 && `, ${booking.children} ${booking.children === 1 ? "child" : "children"}`}
                {booking.travelers.length > 0 && ` · ${[booking.customerName, ...booking.travelers].join(", ")}`}
              </p>
              <PriceLedger totalCents={booking.totalCents} commissionRate={booking.commissionRate} />
              {paid && <p className="font-semibold text-meadow">Deposit paid</p>}
            </div>
          </div>
          <div className="rounded-2xl bg-snow p-4 text-sm text-ink-muted">
            <p className="flex items-center gap-2 font-semibold text-ink">
              <Clock className="size-4" aria-hidden="true" /> History
            </p>
            <ul className="mt-2 space-y-1">
              {booking.timeline.map((e) => (
                <li key={`${e.status}-${e.at}`}>
                  {fmtDateTime(e.at)} · {e.label}
                </li>
              ))}
            </ul>
          </div>
          <a
            href={whatsappUrl(`Hi ${brand.name}! A question about booking ${booking.code}.`)}
            target="_blank"
            rel="noopener noreferrer"
            className="block text-center text-sm font-semibold text-meadow hover:underline"
          >
            Questions? Message us on WhatsApp
          </a>
        </aside>
      </div>
    </div>
  );
}
