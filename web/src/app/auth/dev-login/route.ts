import type { NextRequest } from "next/server";
import { apiFetchAsTraveler, redirectTo, safeNext, SESSION_COOKIE, sessionCookieOptions } from "@/lib/session";

/** Local testing only: Laravel answers 404 unless APP_ENV=local and DEV_LOGIN=true. */
export async function POST(request: NextRequest) {
  const form = await request.formData();
  const api = await apiFetchAsTraveler(
    "auth/dev-login",
    { method: "POST", body: JSON.stringify({ email: form.get("email"), name: form.get("name") || null }) },
    null,
  );
  if (!api.ok) return redirectTo(`/login?error=${api.status === 422 ? "account" : "unavailable"}`);
  const { data } = (await api.json()) as { data: { token: string } };

  const response = redirectTo(safeNext(String(form.get("next") ?? "")));
  response.cookies.set(SESSION_COOKIE, data.token, sessionCookieOptions);
  return response;
}
