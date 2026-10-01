"use client";

import { Heart } from "lucide-react";
import { cn } from "cn";
import { toggleFavorite, usePreferences } from "@/lib/preferences";

export function FavoriteButton({ slug, title, className }: { slug: string; title: string; className?: string }) {
  const { favorites } = usePreferences();
  const saved = favorites.includes(slug);
  return (
    <button
      type="button"
      onClick={() => toggleFavorite(slug)}
      aria-pressed={saved}
      aria-label={saved ? `Remove ${title} from favorites` : `Save ${title} to favorites`}
      className={cn(
        "flex size-9 items-center justify-center rounded-full bg-white/90 text-ink shadow-sm backdrop-blur transition-colors hover:text-kyrgyz",
        className,
      )}
    >
      <Heart className={cn("size-[18px]", saved && "fill-kyrgyz text-kyrgyz")} />
    </button>
  );
}
