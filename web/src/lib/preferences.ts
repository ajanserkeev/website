"use client";

import { useSyncExternalStore } from "react";
import { currencies, type Currency } from "./money";

// Per-visitor conveniences kept in localStorage: display currency and favorite tours (launch document, item 06).
type Preferences = { currency: Currency; favorites: string[] };

const KEY = "tunduk:preferences";
const DEFAULTS: Preferences = { currency: "USD", favorites: [] };
const listeners = new Set<() => void>();
let state: Preferences | null = null;

function read(): Preferences {
  try {
    const parsed = JSON.parse(localStorage.getItem(KEY) ?? "{}") as Partial<Preferences>;
    return {
      currency: currencies.includes(parsed.currency as Currency) ? (parsed.currency as Currency) : "USD",
      favorites: Array.isArray(parsed.favorites) ? parsed.favorites.filter((s) => typeof s === "string") : [],
    };
  } catch {
    return DEFAULTS;
  }
}

function getSnapshot(): Preferences {
  state ??= read();
  return state;
}

function subscribe(listener: () => void) {
  listeners.add(listener);
  const onStorage = (e: StorageEvent) => {
    if (e.key === KEY) {
      state = read();
      listener();
    }
  };
  window.addEventListener("storage", onStorage);
  return () => {
    listeners.delete(listener);
    window.removeEventListener("storage", onStorage);
  };
}

function update(change: (p: Preferences) => Preferences) {
  state = change(getSnapshot());
  try {
    localStorage.setItem(KEY, JSON.stringify(state));
  } catch {
    // Private mode or blocked storage: keep the in-memory value for this visit.
  }
  listeners.forEach((l) => l());
}

export function usePreferences(): Preferences {
  return useSyncExternalStore(subscribe, getSnapshot, () => DEFAULTS);
}

export const setCurrency = (currency: Currency) => update((p) => ({ ...p, currency }));

/** Signed-in travelers also keep favorites on their account (SavedToursSync listens to this event). */
export const FAVORITE_EVENT = "tunduk:favorite";

export const toggleFavorite = (slug: string) => {
  const saved = !getSnapshot().favorites.includes(slug);
  update((p) => ({ ...p, favorites: saved ? [...p.favorites, slug] : p.favorites.filter((s) => s !== slug) }));
  window.dispatchEvent(new CustomEvent(FAVORITE_EVENT, { detail: { slug, saved } }));
};

/** After signing in: the union of this browser's and the account's favorites. */
export const mergeFavorites = (slugs: string[]) =>
  update((p) => ({ ...p, favorites: [...new Set([...p.favorites, ...slugs])] }));

export const currentFavorites = () => getSnapshot().favorites;
