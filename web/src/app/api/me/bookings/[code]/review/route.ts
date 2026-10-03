import { forwardAsTraveler } from "@/lib/session";

export async function POST(request: Request, ctx: RouteContext<"/api/me/bookings/[code]/review">) {
  const { code } = await ctx.params;
  return forwardAsTraveler(`me/bookings/${encodeURIComponent(code)}/review`, request);
}
