import Link from "next/link";
import { TundukMark } from "@/components/brand/tunduk-mark";
import { buttonVariants } from "@/components/ui/button";

export default function NotFound() {
  return (
    <div className="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
      <TundukMark className="size-16 text-line-strong" />
      <h1 className="mt-6 font-display text-3xl font-bold text-ink">This trail ends here</h1>
      <p className="mt-3 text-ink-muted">The page you are looking for doesn&apos;t exist or has moved.</p>
      <div className="mt-8 flex gap-3">
        <Link href="/tours" className={buttonVariants({ variant: "lake" })}>
          Browse tours
        </Link>
        <Link href="/" className={buttonVariants({ variant: "outline" })}>
          Home
        </Link>
      </div>
    </div>
  );
}
