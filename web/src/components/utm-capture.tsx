"use client";

import { useEffect } from "react";

const KEY = "tunduk:utm";
const PARAMS = ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "gclid", "fbclid"];

/** Keeps the first visit's UTM tags so the booking records where the traveler came from (section 10). */
export function UtmCapture() {
  useEffect(() => {
    try {
      if (localStorage.getItem(KEY)) return;
      const url = new URL(window.location.href);
      const utm = Object.fromEntries(PARAMS.map((p) => [p, url.searchParams.get(p)]).filter(([, v]) => v));
      const referrer = document.referrer && !document.referrer.startsWith(window.location.origin) ? document.referrer : null;
      if (Object.keys(utm).length || referrer) {
        localStorage.setItem(KEY, JSON.stringify({ ...utm, ...(referrer ? { referrer: referrer.slice(0, 200) } : {}), landing: url.pathname }));
      }
    } catch {
      // Storage blocked: attribution is best effort.
    }
  }, []);
  return null;
}

export function readUtm(): Record<string, string> | null {
  try {
    return JSON.parse(localStorage.getItem(KEY) ?? "null");
  } catch {
    return null;
  }
}
