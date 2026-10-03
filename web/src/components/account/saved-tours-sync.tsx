"use client";

import { useRouter } from "next/navigation";
import { useEffect } from "react";
import { currentFavorites, FAVORITE_EVENT, mergeFavorites } from "@/lib/preferences";

const save = (slug: string, saved: boolean) =>
  fetch(`/api/me/saved/${encodeURIComponent(slug)}`, { method: saved ? "PUT" : "DELETE" }).catch(() => null);

/**
 * For signed-in travelers: hearts saved in this browser go to the account, the account's saved tours
 * come to this browser, and later clicks are mirrored. Guests keep using localStorage only.
 */
export function SavedToursSync() {
  const router = useRouter();

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const response = await fetch("/api/me/saved").catch(() => null);
      if (!response?.ok || cancelled) return;
      const remote = ((await response.json()) as { data: { slug: string }[] }).data.map((t) => t.slug);
      const missing = currentFavorites().filter((s) => !remote.includes(s));
      await Promise.all(missing.map((s) => save(s, true)));
      if (cancelled) return;
      mergeFavorites(remote);
      // The account page lists saved tours from the server: show the ones just uploaded.
      if (missing.length) router.refresh();
    })();

    const onToggle = (event: Event) => {
      const { slug, saved } = (event as CustomEvent<{ slug: string; saved: boolean }>).detail;
      void save(slug, saved);
    };
    window.addEventListener(FAVORITE_EVENT, onToggle);
    return () => {
      cancelled = true;
      window.removeEventListener(FAVORITE_EVENT, onToggle);
    };
  }, [router]);

  return null;
}
