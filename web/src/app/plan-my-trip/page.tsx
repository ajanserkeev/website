import type { Metadata } from "next";
import Link from "next/link";
import { MessageCircle } from "lucide-react";
import { SectionHeading } from "@/components/section-heading";
import { buttonVariants } from "@/components/ui/button";
import { brand, whatsappUrl } from "@/lib/brand";

export const metadata: Metadata = {
  title: "Plan My Trip",
  description: "Tell us your dates and interests. A local expert will send you a route within 24 hours.",
};

const questions = [
  "When do you want to travel, and for how many days?",
  "Who is travelling: adults, children, ages?",
  "What do you enjoy: horse riding, trekking, yurts, culture, lakes, photography?",
  "How active do you want to be, and have you been at altitude before?",
  "Your budget per person and the kind of stay you prefer.",
];

// The 8-step quiz (step 5.8) replaces this page once inquiries are stored by the API (step 4.10).
export default function PlanMyTripPage() {
  const message = [`Hi ${brand.name}! Please help me plan a trip to Kyrgyzstan.`, ...questions.map((q) => `${q} `)].join("\n");
  return (
    <div className="mx-auto max-w-3xl px-4 py-10 sm:px-6">
      <SectionHeading
        as="h1"
        eyebrow="Tailor-made"
        title="Plan my trip"
        text="Tell us a little about your trip and a local expert in Bishkek will send you a route with prices within 24 hours. No commitment."
        className="mb-8"
      />
      <div className="rounded-2xl border border-line bg-white p-6">
        <h2 className="font-semibold text-ink">What helps us suggest the right route</h2>
        <ol className="mt-4 space-y-3">
          {questions.map((q, i) => (
            <li key={q} className="flex gap-3 text-ink-muted">
              <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-lake-50 text-sm font-bold text-lake">
                {i + 1}
              </span>
              {q}
            </li>
          ))}
        </ol>
        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
          <a
            href={whatsappUrl(message)}
            target="_blank"
            rel="noopener noreferrer"
            className={buttonVariants({ variant: "primary", size: "lg" })}
          >
            <MessageCircle aria-hidden="true" />
            Send on WhatsApp
          </a>
          {brand.email && (
            <a
              href={`mailto:${brand.email}?subject=${encodeURIComponent("Plan my trip")}&body=${encodeURIComponent(message)}`}
              className={buttonVariants({ variant: "outline", size: "lg" })}
            >
              Send by email
            </a>
          )}
        </div>
      </div>
      <p className="mt-6 text-sm text-ink-muted">
        Prefer to browse first?{" "}
        <Link href="/tours" className="font-semibold text-lake hover:underline">
          See all tours
        </Link>
        .
      </p>
    </div>
  );
}
