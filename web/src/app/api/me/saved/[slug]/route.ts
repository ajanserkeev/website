import { forwardAsTraveler } from "@/lib/session";

/** Saved tours follow the traveler across devices (the heart button keeps working without an account). */
export async function PUT(request: Request, ctx: RouteContext<"/api/me/saved/[slug]">) {
  const { slug } = await ctx.params;
  return forwardAsTraveler(`me/saved-tours/${encodeURIComponent(slug)}`, request);
}

export async function DELETE(request: Request, ctx: RouteContext<"/api/me/saved/[slug]">) {
  const { slug } = await ctx.params;
  return forwardAsTraveler(`me/saved-tours/${encodeURIComponent(slug)}`, request);
}
