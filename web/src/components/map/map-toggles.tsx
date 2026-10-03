"use client";

import { Box, Layers } from "lucide-react";
import { cn } from "cn";
import type { Basemap } from "./map-core";

/** Basemap and 3D switches drawn over a map. */
export function MapToggles({
  basemap,
  onBasemap,
  terrain,
  onTerrain,
  className,
}: {
  basemap: Basemap;
  onBasemap: (b: Basemap) => void;
  terrain: boolean;
  onTerrain: (on: boolean) => void;
  className?: string;
}) {
  const button = (active: boolean) =>
    cn(
      "flex h-8 items-center gap-1.5 rounded-md px-2.5 text-xs font-semibold transition-colors",
      active ? "bg-ink text-white" : "text-ink hover:bg-snow",
    );

  return (
    <div className={cn("flex gap-1 rounded-lg bg-white/95 p-1 shadow-card backdrop-blur", className)}>
      <button type="button" className={button(basemap === "map")} onClick={() => onBasemap("map")} aria-pressed={basemap === "map"}>
        <Layers className="size-3.5" aria-hidden="true" />
        Map
      </button>
      <button type="button" className={button(basemap === "satellite")} onClick={() => onBasemap("satellite")} aria-pressed={basemap === "satellite"}>
        Satellite
      </button>
      <button type="button" className={button(terrain)} onClick={() => onTerrain(!terrain)} aria-pressed={terrain}>
        <Box className="size-3.5" aria-hidden="true" />
        3D
      </button>
    </div>
  );
}
