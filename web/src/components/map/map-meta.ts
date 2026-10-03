// Colours and labels of the maps, shared by the map components and their legends.
import type { PlaceKind, RouteMode } from "@/lib/types";

export const PLACE_KINDS: Record<PlaceKind, { label: string; color: string }> = {
  lake: { label: "Lakes", color: "#1d7fc4" },
  pass: { label: "Passes", color: "#7b5ea7" },
  peak: { label: "Peaks & base camps", color: "#475569" },
  canyon: { label: "Canyons", color: "#c2410c" },
  yurt_camp: { label: "Yurt camps", color: "#c98d00" },
  hot_spring: { label: "Hot springs", color: "#db2777" },
  waterfall: { label: "Waterfalls", color: "#0891b2" },
  historical: { label: "History", color: "#92400e" },
  town: { label: "Towns & villages", color: "#172031" },
  park: { label: "National parks", color: "#11804a" },
  viewpoint: { label: "Viewpoints", color: "#65a30d" },
};

export const ROUTE_MODES: Record<RouteMode, { label: string; color: string; dashed: boolean }> = {
  drive: { label: "By car", color: "#2d5d8c", dashed: false },
  hike: { label: "On foot", color: "#11804a", dashed: false },
  horse: { label: "On horseback", color: "#b07d00", dashed: false },
  line: { label: "Off-road", color: "#c4122f", dashed: true },
};

/** Kyrgyzstan with a margin, [west, south, east, north]. */
export const KYRGYZSTAN_BOUNDS: [number, number, number, number] = [69.2, 39.1, 80.3, 43.3];
