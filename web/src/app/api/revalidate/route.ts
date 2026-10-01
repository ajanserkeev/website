import { revalidateTag } from "next/cache";
import { timingSafeEqual } from "node:crypto";
import { CATALOG_TAG } from "@/lib/api";

/**
 * Called by Laravel (App\Jobs\RevalidateFrontend) after the catalog changes in the admin,
 * so edits appear on the site immediately instead of after the 5-minute cache (plan, recommendation 13).
 */
export async function POST(request: Request) {
  const secret = process.env.REVALIDATE_SECRET ?? "";
  const given = request.headers.get("authorization")?.replace(/^Bearer /, "") ?? "";
  const ok = secret.length > 0 && given.length === secret.length && timingSafeEqual(Buffer.from(given), Buffer.from(secret));
  if (!ok) {
    return Response.json({ revalidated: false }, { status: 401 });
  }

  revalidateTag(CATALOG_TAG, { expire: 0 });
  return Response.json({ revalidated: true });
}
