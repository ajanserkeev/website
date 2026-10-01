import { cn } from "cn";

/** Low-contrast felt-pattern divider between major page sections. */
export function ShyrdakDivider({ className }: { className?: string }) {
  return (
    <div className={cn("flex items-center gap-4 text-line-strong", className)} aria-hidden="true">
      <span className="h-px flex-1 bg-line" />
      <svg viewBox="0 0 48 24" className="h-5 w-10" fill="none" stroke="currentColor" strokeWidth="1.5">
        <path d="M24 2 14 12l10 10 10-10L24 2Z" />
        <path d="M2 12h8M38 12h8" />
        <path d="M19 12a5 5 0 0 1 10 0" />
        <circle cx="24" cy="12" r="1.6" fill="currentColor" />
      </svg>
      <span className="h-px flex-1 bg-line" />
    </div>
  );
}
