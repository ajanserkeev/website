// MapLibre setup shared by the site's maps (ported from the GO-Kyrgyzstan prototype's 2D/3D maps).
// Client-only: imported from components loaded with next/dynamic and ssr: false.
import {
  FullscreenControl,
  LngLatBounds,
  Map as MapLibreMap,
  NavigationControl,
  ScaleControl,
  setWorkerUrl,
  type LayerSpecification,
  type StyleSpecification,
} from "maplibre-gl";
import "maplibre-gl/dist/maplibre-gl.css";
import type { LngLat } from "@/lib/types";
import { KYRGYZSTAN_BOUNDS } from "./map-meta";

// Copied to public/ by scripts/vendor-maplibre.mjs: maplibre-gl 6 cannot find its worker inside a bundle.
setWorkerUrl("/vendor/maplibre/maplibre-gl-worker.mjs");

export type Basemap = "map" | "satellite";

/** Our sources and layers carry this prefix so they survive a basemap switch. */
export const OWN = "tu-";

export const FONT = ["Noto Sans Regular"];
export const FONT_BOLD = ["Noto Sans Bold"];

// Free, keyless and open: OpenFreeMap vector tiles, AWS Terrain Tiles (Mapzen) for relief and 3D.
// Esri imagery: check the licence before launch (plan, section "Карты").
const GLYPHS = "https://tiles.openfreemap.org/fonts/{fontstack}/{range}.pbf";
const DEM_TILES = ["https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png"];
const DEM_ATTRIBUTION = '<a href="https://github.com/tilezen/joerd/blob/master/docs/attribution.md">Terrain: Mapzen, AWS</a>';

const STYLES: Record<Basemap, string | StyleSpecification> = {
  map: "https://tiles.openfreemap.org/styles/liberty",
  satellite: {
    version: 8,
    glyphs: GLYPHS,
    sources: {
      satellite: {
        type: "raster",
        tiles: ["https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}"],
        tileSize: 256,
        maxzoom: 18,
        attribution: "Imagery © Esri, Maxar, Earthstar Geographics",
      },
    },
    layers: [{ id: "satellite", type: "raster", source: "satellite" }],
  },
};

export function createMap(container: HTMLElement, { cooperative = false }: { cooperative?: boolean } = {}) {
  const map = new MapLibreMap({
    container,
    style: STYLES.map,
    bounds: KYRGYZSTAN_BOUNDS,
    fitBoundsOptions: { padding: 16 },
    maxPitch: 75,
    attributionControl: { compact: true },
    // Embedded maps don't steal the page scroll: ctrl/two fingers to zoom.
    cooperativeGestures: cooperative,
  });
  map.addControl(new NavigationControl({ visualizePitch: true }), "top-right");
  map.addControl(new FullscreenControl(), "top-right");
  map.addControl(new ScaleControl({ unit: "metric" }), "bottom-left");
  map.once("style.load", () => addRelief(map));
  return map;
}

/** Hillshade under the labels and the elevation source for 3D. */
function addRelief(map: MapLibreMap) {
  map.addSource(`${OWN}dem`, { type: "raster-dem", tiles: DEM_TILES, encoding: "terrarium", tileSize: 256, maxzoom: 14, attribution: DEM_ATTRIBUTION });
  map.addSource(`${OWN}dem-shade`, { type: "raster-dem", tiles: DEM_TILES, encoding: "terrarium", tileSize: 256, maxzoom: 14 });
  const firstLabel = map.getStyle().layers.find((l) => l.type === "symbol")?.id;
  map.addLayer(
    { id: `${OWN}hillshade`, type: "hillshade", source: `${OWN}dem-shade`, paint: { "hillshade-exaggeration": 0.35, "hillshade-shadow-color": "#3b4a5a" } },
    firstLabel,
  );
}

/** Swaps the basemap and carries our sources, layers and 3D terrain over to the new style. */
export function setBasemap(map: MapLibreMap, basemap: Basemap) {
  map.setStyle(STYLES[basemap], {
    diff: false,
    transformStyle: (previous, next) => {
      if (!previous) return next;
      const sources = { ...next.sources };
      for (const [id, source] of Object.entries(previous.sources)) if (id.startsWith(OWN)) sources[id] = source;
      const own = previous.layers.filter((l) => l.id.startsWith(OWN));
      const shade = own.filter((l) => l.type === "hillshade" && basemap === "map");
      const overlays = own.filter((l) => l.type !== "hillshade");
      const labelAt = next.layers.findIndex((l) => l.type === "symbol");
      const base: LayerSpecification[] = labelAt < 0 ? next.layers : next.layers.slice(0, labelAt);
      const labels: LayerSpecification[] = labelAt < 0 ? [] : next.layers.slice(labelAt);
      return { ...next, sources, layers: [...base, ...shade, ...labels, ...overlays], terrain: previous.terrain };
    },
  });
}

export function setTerrain3d(map: MapLibreMap, on: boolean) {
  // Right after a basemap switch the new style is still loading.
  if (!map.isStyleLoaded()) {
    map.once("style.load", () => setTerrain3d(map, on));
    return;
  }
  map.setTerrain(on ? { source: `${OWN}dem`, exaggeration: 1.5 } : null);
  map.easeTo({ pitch: on ? 62 : 0, bearing: on ? -15 : 0, duration: 900 });
}

export function boundsOf(points: LngLat[]): LngLatBounds | null {
  if (!points.length) return null;
  return points.reduce((b, p) => b.extend(p), new LngLatBounds(points[0], points[0]));
}

/**
 * Runs once the first style is in (after the relief is added), without waiting for every tile:
 * "load" would wait for the whole first render, which is slow on mountain tiles.
 */
export function whenStyleReady(map: MapLibreMap, callback: () => void) {
  if (map.isStyleLoaded()) callback();
  else map.once("style.load", callback);
}

export type { MapLibreMap };
