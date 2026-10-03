import { NextResponse, type NextRequest } from "next/server";
import { apiFetchAsTraveler, OAUTH_STATE_COOKIE, redirectTo, safeNext } from "@/lib/session";

/** Starts Google sign-in: our own state cookie against CSRF, Laravel builds the Google URL with its client id. */
export async function GET(request: NextRequest) {
  const state = `${crypto.randomUUID()}${crypto.randomUUID()}`.replaceAll("-", "");
  const next = safeNext(request.nextUrl.searchParams.get("next"));

  const response = await apiFetchAsTraveler(`auth/google/url?state=${state}`, {}, null);
  if (!response.ok) return redirectTo("/login?error=unavailable", 302);
  const { data } = (await response.json()) as { data: { url: string } };

  const redirect = NextResponse.redirect(data.url);
  redirect.cookies.set(OAUTH_STATE_COOKIE, JSON.stringify({ state, next }), {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/auth/google",
    maxAge: 600,
  });
  return redirect;
}
