"use client";

// next/image loader (plan, recommendation 14): Laravel already stores WebP at 480/960/1600 px,
// so the browser picks the nearest prepared size instead of Next.js re-encoding on the server.
const WIDTHS = [480, 960, 1600];
const PREPARED = /-w(?:480|960|1600)\.webp$/;

export default function imageLoader({ src, width }: { src: string; width: number; quality?: number }): string {
  if (!PREPARED.test(src)) return src;
  const size = WIDTHS.find((w) => w >= width) ?? WIDTHS[WIDTHS.length - 1];
  return src.replace(PREPARED, `-w${size}.webp`);
}
