import { proxyPost } from "@/lib/api";

/** Booking request from the form on /tours/[slug]/book. */
export async function POST(request: Request) {
  return proxyPost("bookings", request);
}
