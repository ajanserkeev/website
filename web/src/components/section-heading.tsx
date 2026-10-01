import { cn } from "cn";

export function SectionHeading({
  eyebrow,
  title,
  text,
  as: Tag = "h2",
  className,
}: {
  eyebrow?: string;
  title: string;
  text?: string;
  as?: "h1" | "h2";
  className?: string;
}) {
  return (
    <div className={cn("max-w-2xl", className)}>
      {eyebrow && <p className="text-xs font-bold tracking-wider text-lake uppercase">{eyebrow}</p>}
      <Tag className="mt-1 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">{title}</Tag>
      {text && <p className="mt-2 text-ink-muted">{text}</p>}
    </div>
  );
}
