"use client";

import { Loader2, Star } from "lucide-react";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { cn } from "cn";

/** Review of a completed trip. The API checks the booking is the traveler's and finished. */
export function ReviewForm({ code, tourTitle }: { code: string; tourTitle: string }) {
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [rating, setRating] = useState(0);
  const [hover, setHover] = useState(0);
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!open) {
    return (
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="flex h-10 items-center gap-2 rounded-lg bg-kyrgyz px-4 text-sm font-semibold text-white hover:bg-kyrgyz-hover"
      >
        <Star className="size-4" aria-hidden="true" />
        Rate this trip
      </button>
    );
  }

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!rating) return setError("Choose from 1 to 5 stars.");
    if (body.trim().length < 30) return setError("Tell other travelers a bit more (at least 30 characters).");
    setBusy(true);
    setError(null);
    const response = await fetch(`/api/me/bookings/${encodeURIComponent(code)}/review`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ rating, title: title || null, body }),
    });
    setBusy(false);
    if (response.ok) {
      router.refresh();
      return;
    }
    const answer = (await response.json().catch(() => ({}))) as { message?: string };
    setError(answer.message ?? "We couldn't save the review. Please try again.");
  };

  return (
    <form onSubmit={submit} className="space-y-3 rounded-2xl border border-line bg-snow p-4">
      <p className="font-semibold text-ink">How was {tourTitle}?</p>
      <div className="flex gap-1" role="radiogroup" aria-label="Rating" onMouseLeave={() => setHover(0)}>
        {[1, 2, 3, 4, 5].map((n) => (
          <button
            key={n}
            type="button"
            role="radio"
            aria-checked={rating === n}
            aria-label={`${n} ${n === 1 ? "star" : "stars"}`}
            onClick={() => setRating(n)}
            onMouseEnter={() => setHover(n)}
            className="p-0.5"
          >
            <Star className={cn("size-7", n <= (hover || rating) ? "fill-sun text-sun" : "text-line-strong")} aria-hidden="true" />
          </button>
        ))}
      </div>
      <input
        value={title}
        onChange={(e) => setTitle(e.target.value)}
        maxLength={120}
        placeholder="Title (optional)"
        className="h-10 w-full rounded-lg border border-line-strong bg-white px-3 text-sm"
      />
      <textarea
        value={body}
        onChange={(e) => setBody(e.target.value)}
        rows={4}
        maxLength={3000}
        placeholder="What did you like? What should the next travelers know?"
        className="w-full rounded-lg border border-line-strong bg-white p-3 text-sm"
      />
      {error && <p className="text-sm text-kyrgyz">{error}</p>}
      <div className="flex gap-2">
        <button type="submit" disabled={busy} className="flex h-10 items-center gap-2 rounded-lg bg-ink px-4 text-sm font-semibold text-white disabled:opacity-60">
          {busy && <Loader2 className="size-4 animate-spin" aria-hidden="true" />}
          Publish review
        </button>
        <button type="button" onClick={() => setOpen(false)} className="h-10 rounded-lg px-4 text-sm font-semibold text-ink-muted hover:bg-white">
          Cancel
        </button>
      </div>
      <p className="text-xs text-ink-muted">Shown on the tour page with your first name and a &ldquo;Verified booking&rdquo; badge.</p>
    </form>
  );
}
