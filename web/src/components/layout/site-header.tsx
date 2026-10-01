import Link from "next/link";
import { TundukMark } from "@/components/brand/tunduk-mark";
import { brand } from "@/lib/brand";
import { CurrencySelect, DesktopNav, FavoritesLink, MobileMenu, WhatsAppLink } from "./header-controls";

export function SiteHeader() {
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
          <WhatsAppLink className="hidden md:flex" />
          <MobileMenu />
        </div>
      </div>
    </header>
  );
}
