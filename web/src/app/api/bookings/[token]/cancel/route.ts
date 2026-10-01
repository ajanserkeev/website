import { proxyPost } from "@/lib/api";

/** Cancellation from the "My booking" page. */
export async function POST(request: Request, ctx: RouteContext<"/api/bookings/[token]/cancel">) {
  const { token } = await ctx.params;
  return proxyPost(`bookings/${encodeURIComponent(token)}/cancel`, request);
}
