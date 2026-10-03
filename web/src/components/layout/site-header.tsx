import Link from "next/link";
import { CircleUserRound } from "lucide-react";
import { TundukMark } from "@/components/brand/tunduk-mark";
import { brand } from "@/lib/brand";
import { getSessionToken } from "@/lib/session";
import { CurrencySelect, DesktopNav, FavoritesLink, MobileMenu, WhatsAppLink } from "./header-controls";

export async function SiteHeader() {
  // The cookie is enough for the header; pages that need the traveler ask the API.
  const signedIn = Boolean(await getSessionToken());
  return (
    <header className="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur-md">
      <div className="relative mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:h-[72px]">
        <div className="flex items-center gap-6">
          <Link href="/" className="flex items-center gap-2.5 text-ink">
            <TundukMark className="size-8 text-kyrgyz" />
            <span className="font-display text-lg font-bold tracking-tight">{brand.name}</span>
          </Link>
          <DesktopNav />
        </div>
        <div className="flex items-center gap-1 sm:gap-2">
          <CurrencySelect className="hidden sm:flex" />
          <FavoritesLink />
          <Link
            href={signedIn ? "/account" : "/login"}
            className="flex h-9 items-center gap-1.5 rounded-lg px-2 text-sm font-semibold text-ink-muted hover:bg-secondary hover:text-ink"
            aria-label={signedIn ? "My account" : "Sign in"}
          >
            <CircleUserRound className="size-5" aria-hidden="true" />
            <span className="hidden sm:inline">{signedIn ? "Account" : "Sign in"}</span>
          </Link>
          <WhatsAppLink className="hidden md:flex" />
          <MobileMenu />
        </div>
      </div>
    </header>
  );
}
