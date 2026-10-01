import { useId } from "react";

/** Working version of the tunduk emblem (yurt crown). Final mark comes from the designer (step 2.1). */
export function TundukMark({ className }: { className?: string }) {
  const clip = useId();
  return (
    <svg viewBox="0 0 100 100" fill="none" stroke="currentColor" className={className} aria-hidden="true">
      <circle cx="50" cy="50" r="46" strokeWidth="4" />
      <circle cx="50" cy="50" r="38.5" strokeWidth="4" />
      <clipPath id={clip}>
        <circle cx="50" cy="50" r="38" />
      </clipPath>
      <g clipPath={`url(#${clip})`} strokeWidth="3.4">
        <path d="M8 21C50 24 72 52 73 100" />
        <path d="M8 28C45 30 64 55 66 100" />
        <path d="M8 35C40 37 57 60 59 100" />
        <path d="M92 21C50 24 28 52 27 100" />
        <path d="M92 28C55 30 36 55 34 100" />
        <path d="M92 35C60 37 43 60 41 100" />
      </g>
    </svg>
  );
}
