"use client";

import { Mountain, Route, TrendingUp } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import { Marker, Popup, type ExpressionSpecification, type GeoJSONSource } from "maplibre-gl";
import type { LngLat, RouteMode, TourMapData } from "@/lib/types";
import { boundsOf, createMap, OWN, setBasemap, setTerrain3d, whenStyleReady, type Basemap, type MapLibreMap } from "./map-core";
import { ROUTE_MODES } from "./map-meta";
import { MapToggles } from "./map-toggles";

const MODE_COLOR = [
  "match",
  ["get", "mode"],
  ...Object.entries(ROUTE_MODES).flatMap(([mode, meta]) => [mode, meta.color]),
  "#2d5d8c",
] as unknown as ExpressionSpecification;

const DASHED = ["any", ["==", ["get", "mode"], "line"], ["get", "approximate"]] as unknown as ExpressionSpecification;
const SOLID = ["!", DASHED] as unknown as ExpressionSpecification;

function km(a: LngLat, b: LngLat) {
  const rad = Math.PI / 180;
  const dLat = (b[1] - a[1]) * rad;
  const dLng = (b[0] - a[0]) * rad;
  const h = Math.sin(dLat / 2) ** 2 + Math.cos(a[1] * rad) * Math.cos(b[1] * rad) * Math.sin(dLng / 2) ** 2;
  return 6371 * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
}

/** Point at a distance along the line: the elevation profile and the map share the same kilometres. */
function pointAt(line: LngLat[], target: number): LngLat | null {
  let walked = 0;
  for (let i = 1; i < line.length; i++) {
    const piece = km(line[i - 1], line[i]);
    if (walked + piece >= target && piece > 0) {
      const t = (target - walked) / piece;
      return [line[i - 1][0] + (line[i][0] - line[i - 1][0]) * t, line[i - 1][1] + (line[i][1] - line[i - 1][1]) * t];
    }
    walked += piece;
  }
  return line.at(-1) ?? null;
}

function dayLabel(days: number[]) {
  return days.length > 1 ? `${days[0]}–${days[days.length - 1]}` : String(days[0]);
}

/** Route of a tour with numbered day stops and the elevation profile (from the GO-Kyrgyzstan prototype). */
export default function TourRouteMap({ data, maxAltitudeM }: { data: TourMapData; maxAltitudeM: number | null }) {
  const container = useRef<HTMLDivElement>(null);
  const mapRef = useRef<MapLibreMap | null>(null);
  const [ready, setReady] = useState(false);
  const [basemap, setBasemapState] = useState<Basemap>("map");
  const [terrain, setTerrain] = useState(false);
  const [cursor, setCursor] = useState<number | null>(null);

  const route = data.route;
  const props = route?.properties;
  const line = useMemo<LngLat[]>(() => route?.features.flatMap((f) => f.geometry.coordinates) ?? [], [route]);
  // Several days at one place share a marker: "4–5".
  const stops = useMemo(() => {
    const grouped = new Map<string, { stop: TourMapData["stops"][number]; days: number[] }>();
    for (const stop of data.stops) {
      const entry = grouped.get(stop.slug);
      if (entry) entry.days.push(stop.day);
      else grouped.set(stop.slug, { stop, days: [stop.day] });
    }
    return [...grouped.values()];
  }, [data.stops]);
  const start = props?.waypoints[0];
  const startIsStop = start && data.stops.some((s) => km([s.lng, s.lat], [start.lng, start.lat]) < 1);

  useEffect(() => {
    if (!container.current) return;
    const map = createMap(container.current, { cooperative: true });
    mapRef.current = map;
    const markers: Marker[] = [];

    whenStyleReady(map, () => {
      map.addSource(`${OWN}route`, { type: "geojson", data: route ?? { type: "FeatureCollection", features: [] } });
      map.addSource(`${OWN}cursor`, { type: "geojson", data: { type: "FeatureCollection", features: [] } });
      const join = { "line-join": "round", "line-cap": "round" } as const;
      map.addLayer({ id: `${OWN}route-casing`, type: "line", source: `${OWN}route`, layout: join, paint: { "line-color": "#ffffff", "line-width": 7, "line-opacity": 0.9 } });
      map.addLayer({ id: `${OWN}route`, type: "line", source: `${OWN}route`, filter: SOLID, layout: join, paint: { "line-color": MODE_COLOR, "line-width": 4 } });
      map.addLayer({
        id: `${OWN}route-dashed`,
        type: "line",
        source: `${OWN}route`,
        filter: DASHED,
        layout: join,
        paint: { "line-color": MODE_COLOR, "line-width": 3.5, "line-dasharray": [2, 1.5] },
      });
      map.addLayer({
        id: `${OWN}cursor`,
        type: "circle",
        source: `${OWN}cursor`,
        paint: { "circle-radius": 7, "circle-color": "#c4122f", "circle-stroke-color": "#ffffff", "circle-stroke-width": 3 },
      });

      for (const { stop, days } of stops) {
        const element = document.createElement("button");
        element.type = "button";
        element.className =
          "flex h-8 min-w-8 items-center justify-center rounded-full border-2 border-white bg-kyrgyz px-2 font-display text-sm font-bold text-white shadow-overlay";
        element.textContent = dayLabel(days);
        element.setAttribute("aria-label", `Day ${dayLabel(days)}: ${stop.name}`);
        const popup = new Popup({ offset: 18, closeButton: false }).setDOMContent(popupContent(`Day ${dayLabel(days)}`, stop.name, stop.altitudeM));
        markers.push(new Marker({ element }).setLngLat([stop.lng, stop.lat]).setPopup(popup).addTo(map));
      }
      if (start && !startIsStop) {
        const element = document.createElement("span");
        element.className = "rounded-full border-2 border-white bg-ink px-2 py-0.5 text-xs font-bold text-white shadow-overlay";
        element.textContent = "Start";
        markers.push(new Marker({ element }).setLngLat([start.lng, start.lat]).addTo(map));
      }

      const bounds = boundsOf([...line, ...data.stops.map((s): LngLat => [s.lng, s.lat])]);
      if (bounds) map.fitBounds(bounds, { padding: 48, maxZoom: 11, duration: 0 });
      setReady(true);
    });

    return () => {
      markers.forEach((m) => m.remove());
      map.remove();
      mapRef.current = null;
    };
    // Tour data comes from the server render.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    const map = mapRef.current;
    if (!map || !ready) return;
    const point = cursor === null ? null : pointAt(line, cursor);
    (map.getSource(`${OWN}cursor`) as GeoJSONSource | undefined)?.setData({
      type: "FeatureCollection",
      features: point ? [{ type: "Feature", properties: {}, geometry: { type: "Point", coordinates: point } }] : [],
    });
  }, [cursor, line, ready]);

  const modes = Object.entries(props?.distanceByMode ?? {}) as [RouteMode, number][];
  const highest = props?.maxAltitudeM ?? maxAltitudeM;

  return (
    <div className="space-y-4">
      <div className="relative h-[420px] overflow-hidden rounded-2xl border border-line bg-lake-50 sm:h-[480px]">
        <div ref={container} className="size-full" />
        <MapToggles
          basemap={basemap}
          onBasemap={(b) => {
            if (!mapRef.current || !ready || b === basemap) return;
            setBasemap(mapRef.current, b);
            setBasemapState(b);
          }}
          terrain={terrain}
          onTerrain={(on) => {
            if (!mapRef.current || !ready) return;
            setTerrain3d(mapRef.current, on);
            setTerrain(on);
          }}
          className="absolute top-3 left-3"
        />
      </div>

      {props && (
        <dl className="flex flex-wrap gap-x-8 gap-y-3 text-sm">
          <div>
            <dt className="flex items-center gap-1 text-ink-muted">
              <Route className="size-4 text-lake" aria-hidden="true" />
              Route
            </dt>
            <dd className="font-display text-lg font-bold text-ink tabular">{Math.round(props.distanceKm)} km</dd>
          </div>
          {modes.map(([mode, distance]) => (
            <div key={mode}>
              <dt className="flex items-center gap-1.5 text-ink-muted">
                <span
                  className="inline-block h-1 w-5 rounded-full"
                  style={{ background: ROUTE_MODES[mode].color, opacity: ROUTE_MODES[mode].dashed ? 0.6 : 1 }}
                  aria-hidden="true"
                />
                {ROUTE_MODES[mode].label}
              </dt>
              <dd className="font-display text-lg font-bold text-ink tabular">{Math.round(distance)} km</dd>
            </div>
          ))}
          {props.elevationGainM !== null && (
            <div>
              <dt className="flex items-center gap-1 text-ink-muted">
                <TrendingUp className="size-4 text-lake" aria-hidden="true" />
                Total climb
              </dt>
              <dd className="font-display text-lg font-bold text-ink tabular">{props.elevationGainM.toLocaleString("en-US")} m</dd>
            </div>
          )}
          {highest !== null && (
            <div>
              <dt className="flex items-center gap-1 text-ink-muted">
                <Mountain className="size-4 text-lake" aria-hidden="true" />
                Highest point
              </dt>
              <dd className="font-display text-lg font-bold text-ink tabular">{highest.toLocaleString("en-US")} m</dd>
            </div>
          )}
        </dl>
      )}

      {props?.elevationProfile && props.elevationProfile.length > 1 && (
        <ElevationProfile profile={props.elevationProfile} cursor={cursor} onCursor={setCursor} />
      )}
      <p className="text-xs text-ink-muted">
        Route and heights are approximate, for planning; your guide may adjust the way to the weather and the group.
      </p>
    </div>
  );
}

function popupContent(eyebrow: string, title: string, altitude: number | null) {
  const root = document.createElement("div");
  root.className = "text-sm";
  const top = document.createElement("p");
  top.className = "text-xs font-semibold text-ink-muted uppercase";
  top.textContent = eyebrow;
  const name = document.createElement("p");
  name.className = "font-semibold text-ink";
  name.textContent = title;
  root.append(top, name);
  if (altitude) {
    const alt = document.createElement("p");
    alt.className = "font-mono text-xs text-ink-muted";
    alt.textContent = `${altitude.toLocaleString("en-US")} m`;
    root.append(alt);
  }
  return root;
}

/** SVG profile; moving over it shows the point on the map. */
function ElevationProfile({
  profile,
  cursor,
  onCursor,
}: {
  profile: [number, number][];
  cursor: number | null;
  onCursor: (km: number | null) => void;
}) {
  const W = 1000;
  const H = 160;
  const total = profile[profile.length - 1][0] || 1;
  const heights = profile.map((p) => p[1]);
  const min = Math.floor(Math.min(...heights) / 100) * 100;
  const max = Math.ceil(Math.max(...heights) / 100) * 100;
  const span = Math.max(100, max - min);
  const x = (d: number) => (d / total) * W;
  const y = (h: number) => H - ((h - min) / span) * (H - 12) - 4;
  const line = profile.map((p, i) => `${i ? "L" : "M"}${x(p[0]).toFixed(1)},${y(p[1]).toFixed(1)}`).join(" ");
  const nearest = cursor === null ? null : profile.reduce((best, p) => (Math.abs(p[0] - cursor) < Math.abs(best[0] - cursor) ? p : best));

  const move = (event: React.PointerEvent<SVGSVGElement>) => {
    const box = event.currentTarget.getBoundingClientRect();
    const share = Math.min(1, Math.max(0, (event.clientX - box.left) / box.width));
    onCursor(share * total);
  };

  return (
    <figure>
      <figcaption className="mb-1 flex justify-between text-xs text-ink-muted">
        <span>Elevation profile</span>
        <span className="font-mono">
          {nearest ? `km ${Math.round(nearest[0])} · ${nearest[1].toLocaleString("en-US")} m` : `${min.toLocaleString("en-US")}–${max.toLocaleString("en-US")} m`}
        </span>
      </figcaption>
      <div className="relative">
      <svg
        viewBox={`0 0 ${W} ${H}`}
        preserveAspectRatio="none"
        className="h-36 w-full touch-none rounded-xl bg-snow"
        onPointerMove={move}
        onPointerDown={move}
        onPointerLeave={() => onCursor(null)}
        role="img"
        aria-label={`Elevation from ${min} to ${max} metres over ${Math.round(total)} km`}
      >
        <defs>
          <linearGradient id="elevation-fill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor="#2d5d8c" stopOpacity="0.35" />
            <stop offset="100%" stopColor="#2d5d8c" stopOpacity="0.03" />
          </linearGradient>
        </defs>
        <path d={`${line} L${W},${H} L0,${H} Z`} fill="url(#elevation-fill)" />
        <path d={line} fill="none" stroke="#2d5d8c" strokeWidth="2" vectorEffect="non-scaling-stroke" />
        {nearest && (
          <line x1={x(nearest[0])} x2={x(nearest[0])} y1="0" y2={H} stroke="#c4122f" strokeWidth="1" vectorEffect="non-scaling-stroke" />
        )}
      </svg>
      {nearest && (
        // A circle inside the stretched SVG would turn into an ellipse.
        <span
          className="pointer-events-none absolute size-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-kyrgyz shadow-card"
          style={{ left: `${(x(nearest[0]) / W) * 100}%`, top: `${(y(nearest[1]) / H) * 100}%` }}
          aria-hidden="true"
        />
      )}
      </div>
      <div className="mt-1 flex justify-between font-mono text-xs text-ink-muted">
        <span>0 km</span>
        <span>{Math.round(total)} km</span>
      </div>
    </figure>
  );
}
