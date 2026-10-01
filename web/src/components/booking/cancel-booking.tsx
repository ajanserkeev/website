"use client";

import { Loader2 } from "lucide-react";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { Money } from "@/components/money";

/** Cancellation with the refund shown before confirming (launch document, item 20). */
export function CancelBooking({ token, refundCents, paid }: { token: string; refundCents: number; paid: boolean }) {
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [reason, setReason] = useState("");

  const cancel = async () => {
    setBusy(true);
    setError(null);
    const response = await fetch(`/api/bookings/${encodeURIComponent(token)}/cancel`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ reason: reason || null }),
    });
    setBusy(false);
    if (response.ok) {
      setOpen(false);
      router.refresh();
    } else {
      const body = (await response.json().catch(() => ({}))) as { message?: string };
      setError(body.message ?? "We couldn't cancel online. Please reply to our email or message us on WhatsApp.");
    }
  };

  if (!open) {
    return (
      <button type="button" onClick={() => setOpen(true)} className="text-sm font-semibold text-ink-muted underline hover:text-kyrgyz">
        Cancel this booking
      </button>
    );
  }

  return (
    <div className="space-y-3 rounded-2xl border border-kyrgyz/30 bg-kyrgyz-50 p-4" role="dialog" aria-label="Cancel booking">
      <p className="font-semibold text-ink">Cancel this booking?</p>
      <p className="text-sm text-ink">
        {paid ? (
          refundCents > 0 ? (
            <>
              We&apos;ll refund <Money cents={refundCents} className="font-semibold" /> of your deposit to your card.
            </>
          ) : (
            <>Under the cancellation policy the deposit is not refundable this close to departure.</>
          )
        ) : (
          <>You haven&apos;t paid anything, so nothing will be charged.</>
        )}
      </p>
      <textarea value={reason} onChange={(e) => setReason(e.target.value)} rows={2} placeholder="Reason (optional)" className="w-full rounded-lg border border-line-strong bg-white p-2 text-sm" />
      {error && <p className="text-sm text-kyrgyz">{error}</p>}
      <div className="flex gap-2">
        <button type="button" onClick={cancel} disabled={busy} className="flex h-10 items-center gap-2 rounded-lg bg-ink px-4 text-sm font-semibold text-white disabled:opacity-60">
          {busy && <Loader2 className="size-4 animate-spin" />}
          Yes, cancel
        </button>
        <button type="button" onClick={() => setOpen(false)} className="h-10 rounded-lg px-4 text-sm font-semibold text-ink-muted hover:bg-white">
          Keep my booking
        </button>
      </div>
    </div>
  );
}
