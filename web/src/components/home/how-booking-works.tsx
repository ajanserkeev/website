import { cn } from "cn";
import { PriceLedger } from "@/components/tour/price-ledger";

const steps = [
  { title: "Send a request", text: "Pick a tour and dates. No payment yet." },
  { title: "We confirm in 24 h", text: "We check the spots with the local operator, usually within a few hours." },
  { title: "Pay a deposit to book", text: "A secure card payment in USD, from a link valid for 48 hours." },
  { title: "Pay the rest on arrival", text: "Directly to your operator on day 1, in cash or by card." },
];

/** The booking model in four steps plus a worked example, used on the home page and /how-it-works. */
export function HowBookingWorks({ className }: { className?: string }) {
  return (
    <div className={cn("grid items-start gap-8 lg:grid-cols-12", className)}>
      <ol className="grid gap-4 sm:grid-cols-2 lg:col-span-7">
        {steps.map((step, i) => (
          <li key={step.title} className="rounded-2xl border border-line bg-white p-5">
            <span
              className={cn(
                "flex size-9 items-center justify-center rounded-full font-display text-sm font-bold",
                i === steps.length - 1 ? "bg-meadow-50 text-meadow" : "bg-lake-50 text-lake",
              )}
            >
              {i + 1}
            </span>
            <h3 className="mt-3 font-semibold text-ink">{step.title}</h3>
            <p className="mt-1 text-sm text-ink-muted">{step.text}</p>
          </li>
        ))}
      </ol>
      <div className="rounded-2xl border border-line bg-white p-5 lg:col-span-5">
        <h3 className="font-semibold text-ink">Example: 3-day horse trek for 2</h3>
        <p className="mt-1 text-sm text-ink-muted">2 × $330, operator commission 15%. The price is the same as booking with the operator directly.</p>
        <PriceLedger totalCents={66000} commissionRate={15} className="mt-4" />
        <p className="mt-3 text-xs text-ink-muted">
          Cancel 30+ days before departure for a full deposit refund, 14–29 days for 50%.
        </p>
      </div>
    </div>
  );
}
