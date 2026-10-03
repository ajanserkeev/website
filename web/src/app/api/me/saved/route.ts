import { forwardAsTraveler } from "@/lib/session";

export async function GET(request: Request) {
  return forwardAsTraveler("me/saved-tours", request);
}
