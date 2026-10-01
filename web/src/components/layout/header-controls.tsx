"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Heart, Menu, MessageCircle, X } from "lucide-react";
import { useState } from "react";
import { cn } from "cn";
import { brand, whatsappUrl } from "@/lib/brand";
import { currencies, type Currency } from "@/lib/money";
import { setCurrency, usePreferences } from "@/lib/preferences";
import { mainNav } from "./nav-links";

export function CurrencySelect({ className }: { className?: string }) {
  const { currency } = usePreferences();
  return (
    <label className={cn("relative flex items-center", className)}>
      <span className="sr-only">Display currency</span>
      <select
        value={currency}
        onChange={(e) => setCurrency(e.target.value as Currency)}
        className="h-9 cursor-pointer rounded-lg bg-transparent pr-1 pl-2 text-sm font-semibold text-ink-muted hover:bg-secondary hover:text-ink"
      >
        {currencies.map((c) => (
          <option key={c} value={c}>
            {c}
          </option>
        ))}
      </select>
    </label>
  );
}

export function FavoritesLink() {
  const { favorites } = usePreferences();
  return (
    <Link
      href="/favorites"
      className="relative flex size-9 items-center justify-center rounded-lg text-ink-muted hover:bg-secondary hover:text-ink"
      aria-label={`Saved tours (${favorites.length})`}
    >
      <Heart className="size-5" />
      {favorites.length > 0 && (
        <span className="absolute top-0.5 right-0.5 flex size-4 items-center justify-center rounded-full bg-lake text-[10px] font-bold text-white">
          {favorites.length}
        </span>
      )}
    </Link>
  );
}

export function WhatsAppLink({ className }: { className?: string }) {
  return (
    <a
      href={whatsappUrl(`Hi ${brand.name}! I have a question about a trip.`)}
      target="_blank"
      rel="noopener noreferrer"
      className={cn(
        "flex h-9 items-center gap-1.5 rounded-lg bg-meadow-50 px-3 text-sm font-semibold text-meadow hover:bg-meadow hover:text-white",
        className,
      )}
    >
      <MessageCircle className="size-4" />
      <span>WhatsApp</span>
    </a>
  );
}

export function DesktopNav() {
  const pathname = usePathname();
  return (
    <nav className="hidden items-center gap-1 lg:flex" aria-label="Main">
      {mainNav.map((item) => {
        const active = pathname === item.href || pathname.startsWith(`${item.href}/`);
        return (
          <Link
            key={item.href}
            href={item.href}
            aria-current={active ? "page" : undefined}
            className={cn(
              "rounded-lg px-3 py-2 text-sm font-semibold transition-colors",
              active ? "bg-secondary text-ink" : "text-ink-muted hover:bg-secondary hover:text-ink",
            )}
          >
            {item.label}
          </Link>
        );
      })}
    </nav>
  );
}

export function MobileMenu() {
  const [open, setOpen] = useState(false);
  const pathname = usePathname();
  const [lastPath, setLastPath] = useState(pathname);
  if (pathname !== lastPath) {
    // Close the menu after navigation.
    setLastPath(pathname);
    setOpen(false);
  }
  return (
    <div className="lg:hidden">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
        aria-controls="mobile-menu"
        className="flex size-10 items-center justify-center rounded-lg text-ink hover:bg-secondary"
      >
        {open ? <X className="size-5" /> : <Menu className="size-5" />}
        <span className="sr-only">Menu</span>
      </button>
      {open && (
        <div id="mobile-menu" className="absolute inset-x-0 top-full border-b border-line bg-white shadow-overlay">
          <nav className="mx-auto flex max-w-7xl flex-col px-4 py-3" aria-label="Mobile">
            {mainNav.map((item) => (
              <Link key={item.href} href={item.href} className="rounded-lg px-3 py-3 font-semibold text-ink hover:bg-secondary">
                {item.label}
              </Link>
            ))}
            <div className="mt-2 flex items-center justify-between border-t border-line px-3 pt-3">
              <CurrencySelect />
              <WhatsAppLink />
            </div>
          </nav>
        </div>
      )}
    </div>
  );
}
