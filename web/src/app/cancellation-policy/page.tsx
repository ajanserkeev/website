import type { Metadata } from "next";
import { SectionHeading } from "@/components/section-heading";

export const metadata: Metadata = {
  title: "Cancellation Policy",
  description: "Full deposit refund 30+ days before departure, 50% at 14–29 days, 100% if the operator cancels.",
};

// Same schedule as api RefundPolicy (launch document, section 02). Final wording comes from the lawyer (step 1.3).
const rows = [
  { when: "30 days or more before departure", refund: "100% of the deposit" },
  { when: "14 to 29 days before departure", refund: "50% of the deposit" },
  { when: "Less than 14 days before departure", refund: "No refund" },
  { when: "The operator cancels", refund: "100% of the deposit and help finding a similar tour" },
];

export default function CancellationPolicyPage() {
  return (
    <div className="mx-auto max-w-3xl px-4 py-10 sm:px-6">
      <SectionHeading
        as="h1"
        eyebrow="Booking"
        title="Cancellation policy"
        text="The same simple rules for every tour on the site."
        className="mb-8"
      />
      <div className="overflow-hidden rounded-2xl border border-line bg-white">
        <table className="w-full text-left">
          <thead className="bg-snow text-sm text-ink-muted">
            <tr>
              <th scope="col" className="px-5 py-3 font-semibold">
                When you cancel
              </th>
              <th scope="col" className="px-5 py-3 font-semibold">
                Deposit refund
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-line">
            {rows.map((r) => (
              <tr key={r.when}>
                <td className="px-5 py-4 text-ink">{r.when}</td>
                <td className="px-5 py-4 font-semibold text-ink">{r.refund}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="mt-8 space-y-4 text-ink-muted">
        <p>
          Days are counted to the first day of the tour, Bishkek time. You can cancel from the &quot;My booking&quot;
          page linked in your confirmation email; it shows the refund before you confirm.
        </p>
        <p>
          The balance you pay to the operator on day 1 follows the operator&apos;s own cancellation terms, shown on each
          tour page.
        </p>
      </div>
    </div>
  );
}
