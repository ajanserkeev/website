import "server-only";
import { cookies } from "next/headers";
import { connection, NextResponse } from "next/server";

// Traveler session: the Sanctum token from Laravel lives in an httpOnly cookie, so page scripts never see it.
// Server components and route handlers forward it to the API (plan, recommendation 12: BFF).

export const SESSION_COOKIE = "tt_session";
export const OAUTH_STATE_COOKIE = "tt_oauth_state";
const API_URL = process.env.API_URL ?? "http://localhost:8000";

export interface Me {
  name: string;
  email: string;
  avatarUrl: string | null;
}

export const sessionCookieOptions = {
  httpOnly: true,
  sameSite: "lax" as const,
  secure: process.env.NODE_ENV === "production",
  path: "/",
  maxAge: 60 * 60 * 24 * 30,
};

export async function getSessionToken(): Promise<string | null> {
  return (await cookies()).get(SESSION_COOKIE)?.value ?? null;
}

/** Authenticated call to the Laravel API with the traveler's token. Never cached. */
export async function apiFetchAsTraveler(path: string, init: RequestInit = {}, token?: string | null): Promise<Response> {
  await connection();
  const bearer = token ?? (await getSessionToken());
  return fetch(`${API_URL}/api/v1/${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      ...(init.body ? { "Content-Type": "application/json" } : {}),
      ...(bearer ? { Authorization: `Bearer ${bearer}` } : {}),
      ...init.headers,
    },
    cache: "no-store",
  });
}

/** The signed-in traveler, or null (no cookie, or the token expired or was revoked). */
export async function getMe(): Promise<Me | null> {
  if (!(await getSessionToken())) return null;
  const response = await apiFetchAsTraveler("me");
  if (!response.ok) return null;
  return ((await response.json()) as { data: Me }).data;
}

/** Only relative paths inside the site, so ?next= cannot send people elsewhere after signing in. */
export function safeNext(value: string | null | undefined, fallback = "/account"): string {
  return value && value.startsWith("/") && !value.startsWith("//") && !value.startsWith("/\\") ? value : fallback;
}

/** POST to Laravel and pass its JSON answer (and status) through: the BFF for the account's forms. */
export async function forwardAsTraveler(path: string, request: Request, method = request.method): Promise<Response> {
  const body = method === "GET" || method === "DELETE" ? undefined : await request.text();
  const response = await apiFetchAsTraveler(path, { method, body: body || undefined });
  return new Response(await response.text(), { status: response.status, headers: { "Content-Type": "application/json" } });
}

/**
 * Redirect inside the site with a relative Location. The dev server listens on 0.0.0.0, so absolute URLs built
 * from the request would send the browser to another host, where the session cookie does not exist.
 */
export function redirectTo(path: string, status: 302 | 303 = 303): NextResponse {
  return new NextResponse(null, { status, headers: { Location: path } });
}
