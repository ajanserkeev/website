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

export const toggleFavorite = (slug: string) =>
  update((p) => ({
    ...p,
    favorites: p.favorites.includes(slug) ? p.favorites.filter((s) => s !== slug) : [...p.favorites, slug],
  }));
