import type { Metadata } from "next";
import Link from "next/link";
import { HowBookingWorks } from "@/components/home/how-booking-works";
import { SectionHeading } from "@/components/section-heading";

export const metadata: Metadata = {
  title: "How Booking Works",
  description: "Send a request, we confirm within 24 hours, you pay a small deposit and the rest to your operator on day 1.",
};

const faqs = [
  {
    q: "Why pay a deposit to you and the rest to the operator?",
    a: "The deposit is our fee for finding, checking and booking the operator. The price is the same as booking with the operator directly; the operator pays our commission from their margin.",
  },
  {
    q: "When do I pay?",
    a: "Only after the operator confirms your spots. We email you a secure payment link that is valid for 48 hours.",
  },
  {
    q: "How do I pay the rest?",
    a: "Directly to your operator on the first day of the tour, in cash or by card if the operator accepts cards. The amount is shown in your voucher.",
  },
  {
    q: "What if the operator cancels?",
    a: "You get 100% of your deposit back, and we help you find a similar tour on the same dates.",
  },
];

export default function HowItWorksPage() {
  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <SectionHeading
        as="h1"
        eyebrow="Booking"
        title="How booking works"
        text="You never pay before the operator confirms your spots."
        className="mb-8"
      />
      <HowBookingWorks />
      <section className="mt-14 max-w-3xl">
        <h2 className="font-display text-2xl font-bold text-ink">Questions about paying</h2>
        <dl className="mt-6 space-y-6">
          {faqs.map((f) => (
            <div key={f.q}>
              <dt className="font-semibold text-ink">{f.q}</dt>
              <dd className="mt-1 text-ink-muted">{f.a}</dd>
            </div>
          ))}
        </dl>
        <p className="mt-8 text-ink-muted">
          Read the full{" "}
          <Link href="/cancellation-policy" className="font-semibold text-lake hover:underline">
            cancellation policy
          </Link>
          .
        </p>
      </section>
    </div>
  );
}
