import { apiFetchAsTraveler, redirectTo, SESSION_COOKIE } from "@/lib/session";

export async function POST() {
  // Revoke the token in Laravel too, so a copied cookie stops working.
  await apiFetchAsTraveler("auth/logout", { method: "POST" }).catch(() => null);
  const response = redirectTo("/");
  response.cookies.delete(SESSION_COOKIE);
  return response;
}
