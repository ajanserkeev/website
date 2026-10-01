import Link from "next/link";
import { TundukMark } from "@/components/brand/tunduk-mark";
import { brand, whatsappUrl } from "@/lib/brand";

const columns = [
  {
    title: "Discover",
    links: [
      { href: "/tours", label: "All tours" },
      { href: "/destinations", label: "Destinations" },
      { href: "/activities", label: "Activities" },
      { href: "/guides", label: "Travel guides" },
    ],
  },
  {
    title: "Booking",
    links: [
      { href: "/how-it-works", label: "How booking works" },
      { href: "/cancellation-policy", label: "Cancellation policy" },
      { href: "/plan-my-trip", label: "Plan my trip" },
      { href: "/favorites", label: "Saved tours" },
    ],
  },
];

export function SiteFooter() {
  return (
    <footer className="mt-auto bg-ink text-slate-300">
      <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
          <div className="lg:col-span-2">
            <Link href="/" className="flex items-center gap-2.5 text-white">
              <TundukMark className="size-8 text-white" />
              <span className="font-display text-lg font-bold">{brand.name}</span>
            </Link>
            <p className="mt-4 max-w-sm text-sm leading-relaxed text-slate-400">
              Tours in Kyrgyzstan run by verified local operators. Pay a small deposit to book and the rest to your
              operator on arrival.
            </p>
            <div className="mt-6 flex flex-wrap gap-4 text-sm">
              <a href={whatsappUrl(`Hi ${brand.name}!`)} target="_blank" rel="noopener noreferrer" className="hover:text-white">
                WhatsApp
              </a>
              {brand.email && (
                <a href={`mailto:${brand.email}`} className="hover:text-white">
                  {brand.email}
                </a>
              )}
            </div>
          </div>
          {columns.map((col) => (
            <div key={col.title}>
              <h2 className="text-sm font-semibold tracking-wider text-white uppercase">{col.title}</h2>
              <ul className="mt-4 space-y-2.5 text-sm">
                {col.links.map((l) => (
                  <li key={l.href}>
                    <Link href={l.href} className="hover:text-white">
                      {l.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
        <div className="mt-12 flex flex-col gap-4 border-t border-white/10 pt-6 text-sm text-slate-400 md:flex-row md:items-center md:justify-between">
          <p>
            © {new Date().getFullYear()} {brand.name}. Prices in US dollars.
          </p>
          <div className="flex items-center gap-2">
            <span>Secure card payment</span>
            <span className="rounded bg-white/10 px-2 py-0.5 font-mono text-xs font-bold text-white">VISA</span>
            <span className="rounded bg-white/10 px-2 py-0.5 font-mono text-xs font-bold text-white">Mastercard</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
