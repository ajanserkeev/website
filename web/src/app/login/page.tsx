import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { Heart, Star, Ticket } from "lucide-react";
import { apiFetchAsTraveler, getMe, safeNext } from "@/lib/session";

export const metadata: Metadata = {
  title: "Sign in",
  robots: { index: false },
};

const ERRORS: Record<string, string> = {
  cancelled: "Sign-in was cancelled.",
  expired: "The sign-in link expired. Please try again.",
  google: "Google sign-in didn't work. Please try again.",
  account: "This email belongs to a team or partner account. Please use another Google account.",
  unavailable: "Sign-in is not available right now.",
};

async function providers(): Promise<{ google: boolean; dev: boolean }> {
  const response = await apiFetchAsTraveler("auth/providers", {}, null).catch(() => null);
  return response?.ok ? ((await response.json()) as { data: { google: boolean; dev: boolean } }).data : { google: false, dev: false };
}

/** No passwords for travelers: Google only (and a test sign-in on the developer's machine). */
export default async function LoginPage(props: PageProps<"/login">) {
  const query = await props.searchParams;
  const next = safeNext(typeof query.next === "string" ? query.next : undefined);
  if (await getMe()) redirect(next);
  const available = await providers();
  const error = typeof query.error === "string" ? ERRORS[query.error] : undefined;

  return (
    <div className="mx-auto grid max-w-5xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 md:py-20">
      <div>
        <h1 className="font-display text-3xl font-bold tracking-tight text-ink">Your trips in one place</h1>
        <p className="mt-3 text-ink-muted">Booking doesn&apos;t need an account. Signing in adds:</p>
        <ul className="mt-6 space-y-4">
          {[
            { icon: Ticket, title: "All your bookings", text: "Including the ones you made before signing in with the same email." },
            { icon: Heart, title: "Saved tours on every device", text: "Hearts you tap on your phone show up on your laptop." },
            { icon: Star, title: "Reviews after your trip", text: "Rate the tour once you're back. Only travelers who went can review." },
          ].map(({ icon: Icon, title, text }) => (
            <li key={title} className="flex gap-3">
              <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-lake-50 text-lake">
                <Icon className="size-5" aria-hidden="true" />
              </span>
              <span>
                <span className="block font-semibold text-ink">{title}</span>
                <span className="text-sm text-ink-muted">{text}</span>
              </span>
            </li>
          ))}
        </ul>
      </div>

      <div className="rounded-3xl border border-line bg-white p-6 shadow-card sm:p-8">
        <h2 className="font-display text-xl font-bold text-ink">Sign in</h2>
        {error && <p className="mt-4 rounded-lg bg-kyrgyz-50 p-3 text-sm text-kyrgyz">{error}</p>}

        {available.google ? (
          <a
            href={`/auth/google?next=${encodeURIComponent(next)}`}
            className="mt-6 flex h-12 items-center justify-center gap-3 rounded-lg border border-line-strong bg-white font-semibold text-ink hover:bg-snow"
          >
            <svg viewBox="0 0 48 48" className="size-5" aria-hidden="true">
              <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z" />
              <path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z" />
              <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z" />
              <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z" />
            </svg>
            Continue with Google
          </a>
        ) : (
          <p className="mt-6 text-sm text-ink-muted">Google sign-in is being set up.</p>
        )}

        {available.dev && (
          <form action="/auth/dev-login" method="post" className="mt-8 space-y-3 rounded-2xl border border-dashed border-sun bg-sun-50 p-4">
            <p className="text-sm font-semibold text-ink">Test sign-in (local development only)</p>
            <input type="hidden" name="next" value={next} />
            <label className="block text-sm">
              <span className="text-ink-muted">Email</span>
              <input
                name="email"
                type="email"
                required
                defaultValue="tourist@example.com"
                className="mt-1 h-10 w-full rounded-lg border border-line-strong bg-white px-3 text-ink"
              />
            </label>
            <button type="submit" className="h-10 w-full rounded-lg bg-ink text-sm font-semibold text-white hover:bg-ink/90">
              Sign in as a test traveler
            </button>
          </form>
        )}

        <p className="mt-6 text-xs text-ink-muted">We only use your name, email and photo from Google.</p>
      </div>
    </div>
  );
}
