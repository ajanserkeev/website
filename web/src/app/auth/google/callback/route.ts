import type { NextRequest } from "next/server";
import { apiFetchAsTraveler, OAUTH_STATE_COOKIE, redirectTo, safeNext, SESSION_COOKIE, sessionCookieOptions } from "@/lib/session";

/** Google sends the traveler back here; Laravel exchanges the code and returns the site's token. */
export async function GET(request: NextRequest) {
  const params = request.nextUrl.searchParams;
  let saved: { state?: string; next?: string } = {};
  try {
    saved = JSON.parse(request.cookies.get(OAUTH_STATE_COOKIE)?.value ?? "{}");
  } catch {}

  const fail = (reason: string) => {
    const response = redirectTo(`/login?error=${reason}`, 302);
    response.cookies.delete({ name: OAUTH_STATE_COOKIE, path: "/auth/google" });
    return response;
  };

  if (params.get("error")) return fail("cancelled");
  if (!saved.state || params.get("state") !== saved.state || !params.get("code")) return fail("expired");

  const api = await apiFetchAsTraveler("auth/google", { method: "POST", body: JSON.stringify({ code: params.get("code") }) }, null);
  if (!api.ok) return fail(api.status === 422 ? "account" : "google");
  const { data } = (await api.json()) as { data: { token: string } };

  const response = redirectTo(safeNext(saved.next), 302);
  response.cookies.set(SESSION_COOKIE, data.token, sessionCookieOptions);
  response.cookies.delete({ name: OAUTH_STATE_COOKIE, path: "/auth/google" });
  return response;
}
