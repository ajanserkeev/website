import "server-only";
import { connection } from "next/server";
import { getSessionToken } from "./session";

/** All catalog data is tagged; Laravel calls /api/revalidate after admin edits (plan, recommendation 13). */
export const CATALOG_TAG = "catalog";

const API_URL = process.env.API_URL ?? "http://localhost:8000";

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    path: string,
  ) {
    super(`API ${status} for ${path}`);
  }
}

/**
 * GET from the Laravel API v1. Pages render on request (connection()), so `next build` never needs the API;
 * responses are cached for 5 minutes and dropped right away when the admin changes the catalog.
 * Returns null on 404.
 */
export async function apiGet<T>(path: string, params?: Record<string, string | number | string[] | null | undefined>): Promise<T | null> {
  await connection();

  const query = new URLSearchParams();
  for (const [key, value] of Object.entries(params ?? {})) {
    if (value === null || value === undefined || value === "") continue;
    if (Array.isArray(value)) value.forEach((v) => query.append(`${key}[]`, v));
    else query.set(key, String(value));
  }
  const url = `${API_URL}/api/v1/${path}${query.size ? `?${query}` : ""}`;

  const response = await fetch(url, {
    headers: { Accept: "application/json" },
    next: { revalidate: 300, tags: [CATALOG_TAG] },
  });
  if (response.status === 404) return null;
  if (!response.ok) throw new ApiError(response.status, path);

  const body = (await response.json()) as { data: T };
  return body.data;
}

/** Private, never cached: "My booking" data must not be shared between visitors. */
export async function apiGetPrivate<T>(path: string): Promise<T | null> {
  await connection();
  const response = await fetch(`${API_URL}/api/v1/${path}`, { headers: { Accept: "application/json" }, cache: "no-store" });
  if (response.status === 404) return null;
  if (!response.ok) throw new ApiError(response.status, path);
  return ((await response.json()) as { data: T }).data;
}

/**
 * Forwards a traveler's POST to Laravel from a route handler (BFF, plan recommendation 12),
 * passing the traveler's IP so the API rate limit counts people, not the Next.js server, and their session
 * token so a booking made while signed in shows up in their account.
 */
export async function proxyPost(path: string, request: Request): Promise<Response> {
  const forwardedFor = request.headers.get("x-forwarded-for") ?? request.headers.get("x-real-ip") ?? "";
  const token = await getSessionToken();
  const response = await fetch(`${API_URL}/api/v1/${path}`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(forwardedFor ? { "X-Forwarded-For": forwardedFor } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: await request.text(),
    cache: "no-store",
  });
  return new Response(await response.text(), {
    status: response.status,
    headers: { "Content-Type": "application/json" },
  });
}
