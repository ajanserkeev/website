import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { redirect } from "next/navigation";
import { CalendarDays, LogOut, Star, Users } from "lucide-react";
import { ReviewForm } from "@/components/account/review-form";
import { Money } from "@/components/money";
import { TourCard } from "@/components/tour/tour-card";
import { apiFetchAsTraveler, getMe } from "@/lib/session";
import type { Photo, TourSummary } from "@/lib/types";

export const metadata: Metadata = {
  title: "My account",
  robots: { index: false },
};

interface AccountBooking {
  code: string;
  status: string;
  statusLabel: string;
  tour: { slug: string; title: string; image: Photo | null };
  dateFrom: string;
  dateTo: string;
  travelers: number;
  totalCents: number;
  manageUrl: string | null;
  canReview: boolean;
  review: { rating: number; title: string | null; body: string; published: boolean } | null;
}

async function load<T>(path: string): Promise<T[]> {
  const response = await apiFetchAsTraveler(path);
  return response.ok ? ((await response.json()) as { data: T[] }).data : [];
}

const dates = (from: string, to: string) => {
  const f = (d: string, o: Intl.DateTimeFormatOptions) => new Date(`${d}T00:00:00Z`).toLocaleDateString("en-GB", { ...o, timeZone: "UTC" });
  return from === to ? f(from, { day: "numeric", month: "short", year: "numeric" }) : `${f(from, { day: "numeric", month: "short" })} – ${f(to, { day: "numeric", month: "short", year: "numeric" })}`;
};

const statusTone: Record<string, string> = {
  completed: "bg-meadow-50 text-meadow",
  voucher_sent: "bg-lake-50 text-lake",
  deposit_paid: "bg-lake-50 text-lake",
  awaiting_payment: "bg-sun-50 text-ink",
};

export default async function AccountPage() {
  const me = await getMe();
  if (!me) redirect("/login?next=/account");
  const [bookings, saved] = await Promise.all([load<AccountBooking>("me/bookings"), load<TourSummary>("me/saved-tours")]);
  const toReview = bookings.filter((b) => b.canReview);

  return (
    <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
      <header className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          {me.avatarUrl ? (
            <Image src={me.avatarUrl} alt="" width={56} height={56} className="size-14 rounded-full" unoptimized />
          ) : (
            <span className="flex size-14 items-center justify-center rounded-full bg-lake font-display text-xl font-bold text-white">
              {me.name.slice(0, 1)}
            </span>
          )}
          <div>
            <h1 className="font-display text-2xl font-bold tracking-tight text-ink">Hi, {me.name.split(" ")[0]}</h1>
            <p className="text-sm text-ink-muted">{me.email}</p>
          </div>
        </div>
        <form action="/auth/logout" method="post">
          <button type="submit" className="flex h-10 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-ink-muted hover:bg-secondary hover:text-ink">
            <LogOut className="size-4" aria-hidden="true" />
            Sign out
          </button>
        </form>
      </header>

      {toReview.length > 0 && (
        <p className="mt-6 flex items-center gap-2 rounded-2xl bg-sun-50 p-4 text-sm font-semibold text-ink">
          <Star className="size-5 fill-sun text-sun" aria-hidden="true" />
          You&apos;re back from {toReview.length === 1 ? toReview[0].tour.title : `${toReview.length} trips`}. How was it? Your review helps the next travelers.
        </p>
      )}

      <section className="mt-10">
        <h2 className="mb-4 font-display text-xl font-bold text-ink">My bookings</h2>
        {bookings.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-line-strong p-6 text-ink-muted">
            No bookings yet. Bookings made with {me.email} appear here, even if you booked before signing in.{" "}
            <Link href="/tours" className="font-semibold text-lake hover:underline">
              Browse tours
            </Link>
          </p>
        ) : (
          <ul className="space-y-4">
            {bookings.map((b) => (
              <li key={b.code} className="flex flex-col gap-4 rounded-2xl border border-line bg-white p-4 sm:flex-row">
                <Link href={`/tours/${b.tour.slug}`} className="relative aspect-[4/3] w-full shrink-0 overflow-hidden rounded-xl bg-lake-50 sm:w-48">
                  {b.tour.image && <Image src={b.tour.image.src} alt={b.tour.image.alt} fill sizes="192px" className="object-cover" />}
                </Link>
                <div className="flex min-w-0 flex-1 flex-col gap-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className={`rounded-md px-2 py-0.5 text-xs font-semibold ${statusTone[b.status] ?? "bg-snow text-ink-muted"}`}>{b.statusLabel}</span>
                    <span className="font-mono text-xs text-ink-muted">{b.code}</span>
                  </div>
                  <Link href={`/tours/${b.tour.slug}`} className="text-lg font-semibold text-ink hover:text-lake">
                    {b.tour.title}
                  </Link>
                  <p className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-ink-muted">
                    <span className="flex items-center gap-1">
                      <CalendarDays className="size-4" aria-hidden="true" />
                      {dates(b.dateFrom, b.dateTo)}
                    </span>
                    <span className="flex items-center gap-1">
                      <Users className="size-4" aria-hidden="true" />
                      {b.travelers} {b.travelers === 1 ? "traveler" : "travelers"}
                    </span>
                    <Money cents={b.totalCents} className="font-semibold text-ink" />
                  </p>
                  <div className="mt-auto flex flex-wrap items-start gap-3 pt-2">
                    {b.manageUrl && (
                      <a href={new URL(b.manageUrl).pathname} className="flex h-10 items-center rounded-lg border border-line-strong px-4 text-sm font-semibold text-ink hover:bg-snow">
                        Booking details
                      </a>
                    )}
                    {b.canReview && <ReviewForm code={b.code} tourTitle={b.tour.title} />}
                  </div>
                  {b.review && (
                    <blockquote className="mt-2 rounded-xl bg-snow p-3 text-sm">
                      <p className="flex gap-0.5" aria-label={`${b.review.rating} out of 5`}>
                        {Array.from({ length: 5 }, (_, i) => (
                          <Star key={i} className={i < b.review!.rating ? "size-4 fill-sun text-sun" : "size-4 text-line-strong"} aria-hidden="true" />
                        ))}
                      </p>
                      {b.review.title && <p className="mt-1 font-semibold text-ink">{b.review.title}</p>}
                      <p className="mt-1 text-ink-muted">{b.review.body}</p>
                      <p className="mt-1 text-xs text-ink-muted">{b.review.published ? "Published on the tour page" : "Hidden by the moderators"}</p>
                    </blockquote>
                  )}
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="mt-12">
        <h2 className="mb-4 font-display text-xl font-bold text-ink">Saved tours</h2>
        {saved.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-line-strong p-6 text-ink-muted">Tap the heart on any tour to save it here.</p>
        ) : (
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {saved.map((t) => (
              <TourCard key={t.slug} tour={t} />
            ))}
          </div>
        )}
      </section>
    </div>
  );
}
