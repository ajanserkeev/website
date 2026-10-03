"use client";

import Image from "next/image";
import Link from "next/link";
import { ArrowRight, Clock, MapPin, Mountain, Route, X } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import type { ExpressionSpecification, GeoJSONSource } from "maplibre-gl";
import { cn } from "cn";
import { Money } from "@/components/money";
import type { ExploreMapData, MapPlace, MapTour, PlaceKind } from "@/lib/types";
import { boundsOf, createMap, FONT_BOLD, OWN, setBasemap, setTerrain3d, whenStyleReady, type Basemap, type MapLibreMap } from "./map-core";
import { PLACE_KINDS } from "./map-meta";
import { MapToggles } from "./map-toggles";

type Selection = { type: "place"; slug: string } | { type: "tour"; slug: string } | null;

const KIND_COLOR = [
  "match",
  ["get", "kind"],
  ...Object.entries(PLACE_KINDS).flatMap(([kind, meta]) => [kind, meta.color]),
  "#536074",
] as unknown as ExpressionSpecification;

function placesGeoJson(places: MapPlace[]): GeoJSON.FeatureCollection {
  return {
    type: "FeatureCollection",
    features: places.map((p) => ({
      type: "Feature",
      properties: { slug: p.slug, name: p.name, kind: p.kind, featured: p.featured },
      geometry: { type: "Point", coordinates: [p.lng, p.lat] },
    })),
  };
}

function toursGeoJson(tours: MapTour[]): GeoJSON.FeatureCollection {
  return {
    type: "FeatureCollection",
    features: tours.map((t) => ({
      type: "Feature",
      properties: { slug: t.slug, title: t.title },
      geometry: { type: "LineString", coordinates: t.line },
    })),
  };
}

const NONE = ["==", ["get", "slug"], ""] as unknown as ExpressionSpecification;
const bySlug = (slug: string) => ["==", ["get", "slug"], slug] as unknown as ExpressionSpecification;

/**
 * "Explore Kyrgyzstan": places of interest by kind and the routes of all tours (from the GO-Kyrgyzstan
 * prototype's interactive map). "full" is the /map page with its side panel, "compact" the home page block.
 */
export default function ExploreMap({
  data,
  variant = "full",
  initialPlace,
}: {
  data: ExploreMapData;
  variant?: "full" | "compact";
  initialPlace?: string;
}) {
  const container = useRef<HTMLDivElement>(null);
  const mapRef = useRef<MapLibreMap | null>(null);
  const [ready, setReady] = useState(false);
  const [basemap, setBasemapState] = useState<Basemap>("map");
  const [terrain, setTerrain] = useState(false);
  const [showTours, setShowTours] = useState(true);
  const [hiddenKinds, setHiddenKinds] = useState<PlaceKind[]>([]);
  const [selection, setSelection] = useState<Selection>(
    initialPlace && data.places.some((p) => p.slug === initialPlace) ? { type: "place", slug: initialPlace } : null,
  );

  const places = useMemo(() => new Map(data.places.map((p) => [p.slug, p])), [data.places]);
  const tours = useMemo(() => new Map(data.tours.map((t) => [t.slug, t])), [data.tours]);
  const kinds = useMemo(() => [...new Set(data.places.map((p) => p.kind))], [data.places]);

  // Map and its layers, once.
  useEffect(() => {
    if (!container.current) return;
    const map = createMap(container.current, { cooperative: variant === "compact" });
    mapRef.current = map;

    whenStyleReady(map, () => {
      map.addSource(`${OWN}tours`, { type: "geojson", data: toursGeoJson(data.tours) });
      map.addSource(`${OWN}places`, { type: "geojson", data: placesGeoJson(data.places) });
      map.addLayer({
        id: `${OWN}tours-casing`,
        type: "line",
        source: `${OWN}tours`,
        layout: { "line-join": "round", "line-cap": "round" },
        paint: { "line-color": "#ffffff", "line-width": 5, "line-opacity": 0.8 },
      });
      map.addLayer({
        id: `${OWN}tours`,
        type: "line",
        source: `${OWN}tours`,
        layout: { "line-join": "round", "line-cap": "round" },
        paint: { "line-color": "#2d5d8c", "line-width": 2.5, "line-opacity": 0.75 },
      });
      map.addLayer({
        id: `${OWN}tours-active`,
        type: "line",
        source: `${OWN}tours`,
        filter: NONE,
        layout: { "line-join": "round", "line-cap": "round" },
        paint: { "line-color": "#c4122f", "line-width": 4.5 },
      });
      map.addLayer({
        id: `${OWN}places-active`,
        type: "circle",
        source: `${OWN}places`,
        filter: NONE,
        paint: { "circle-radius": 14, "circle-color": "#c4122f", "circle-opacity": 0.25 },
      });
      map.addLayer({
        id: `${OWN}places`,
        type: "circle",
        source: `${OWN}places`,
        paint: {
          "circle-color": KIND_COLOR,
          "circle-radius": ["interpolate", ["linear"], ["zoom"], 5, ["case", ["get", "featured"], 5, 3.5], 10, ["case", ["get", "featured"], 9, 7]],
          "circle-stroke-color": "#ffffff",
          "circle-stroke-width": 2,
        },
      });
      map.addLayer({
        id: `${OWN}places-labels`,
        type: "symbol",
        source: `${OWN}places`,
        layout: {
          // Main places from the start, the rest when zoomed in.
          "text-field": ["step", ["zoom"], ["case", ["get", "featured"], ["get", "name"], ""], 8, ["get", "name"]],
          "text-font": FONT_BOLD,
          "text-size": 12,
          "text-offset": [0, 1.1],
          "text-anchor": "top",
          "text-optional": true,
        },
        paint: { "text-color": "#172031", "text-halo-color": "#ffffff", "text-halo-width": 1.6 },
      });

      map.on("click", `${OWN}places`, (e) => {
        const slug = e.features?.[0]?.properties?.slug;
        if (typeof slug === "string") setSelection({ type: "place", slug });
      });
      map.on("click", `${OWN}tours`, (e) => {
        if (map.queryRenderedFeatures(e.point, { layers: [`${OWN}places`] }).length) return;
        const slug = e.features?.[0]?.properties?.slug;
        if (typeof slug === "string") setSelection({ type: "tour", slug });
      });
      for (const layer of [`${OWN}places`, `${OWN}tours`]) {
        map.on("mouseenter", layer, () => (map.getCanvas().style.cursor = "pointer"));
        map.on("mouseleave", layer, () => (map.getCanvas().style.cursor = ""));
      }
      setReady(true);
    });

    return () => {
      map.remove();
      mapRef.current = null;
    };
    // The data comes from the server render and does not change while the page is open.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Highlight and frame the selection.
  useEffect(() => {
    const map = mapRef.current;
    if (!map || !ready) return;
    const place = selection?.type === "place" ? places.get(selection.slug) : undefined;
    const tour = selection?.type === "tour" ? tours.get(selection.slug) : undefined;
    map.setFilter(`${OWN}places-active`, place ? bySlug(place.slug) : NONE);
    map.setFilter(`${OWN}tours-active`, tour ? bySlug(tour.slug) : NONE);
    if (place) map.easeTo({ center: [place.lng, place.lat], zoom: Math.max(map.getZoom(), 8.5), duration: 800 });
    const bounds = tour ? boundsOf(tour.line) : null;
    if (bounds) map.fitBounds(bounds, { padding: variant === "full" ? 64 : 32, maxZoom: 11, duration: 800 });
  }, [selection, ready, places, tours, variant]);

  // Filters.
  useEffect(() => {
    const map = mapRef.current;
    if (!map || !ready) return;
    const filter = hiddenKinds.length
      ? (["!", ["in", ["get", "kind"], ["literal", hiddenKinds]]] as unknown as ExpressionSpecification)
      : null;
    map.setFilter(`${OWN}places`, filter);
    map.setFilter(`${OWN}places-labels`, filter);
    for (const layer of [`${OWN}tours`, `${OWN}tours-casing`]) map.setLayoutProperty(layer, "visibility", showTours ? "visible" : "none");
  }, [hiddenKinds, showTours, ready]);

  const changeBasemap = (b: Basemap) => {
    if (!mapRef.current || !ready || b === basemap) return;
    setBasemap(mapRef.current, b);
    setBasemapState(b);
  };
  const changeTerrain = (on: boolean) => {
    if (!mapRef.current || !ready) return;
    setTerrain3d(mapRef.current, on);
    setTerrain(on);
  };

  // Keep the GeoJSON in sync if Fast Refresh swaps the data in development.
  useEffect(() => {
    const map = mapRef.current;
    if (!map || !ready) return;
    (map.getSource(`${OWN}places`) as GeoJSONSource | undefined)?.setData(placesGeoJson(data.places));
    (map.getSource(`${OWN}tours`) as GeoJSONSource | undefined)?.setData(toursGeoJson(data.tours));
  }, [data, ready]);

  const selectedPlace = selection?.type === "place" ? places.get(selection.slug) : undefined;
  const selectedTour = selection?.type === "tour" ? tours.get(selection.slug) : undefined;

  const filters = (
    <div className="flex flex-wrap gap-1.5">
      <button
        type="button"
        onClick={() => setShowTours((v) => !v)}
        aria-pressed={showTours}
        className={cn(
          "flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold transition-colors",
          showTours ? "border-lake bg-lake text-white" : "border-line bg-white text-ink-muted",
        )}
      >
        <Route className="size-3.5" aria-hidden="true" />
        Tour routes
      </button>
      {kinds.map((kind) => {
        const on = !hiddenKinds.includes(kind);
        return (
          <button
            key={kind}
            type="button"
            aria-pressed={on}
            onClick={() => setHiddenKinds((h) => (on ? [...h, kind] : h.filter((k) => k !== kind)))}
            className={cn(
              "flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold transition-colors",
              on ? "border-line-strong bg-white text-ink" : "border-line bg-snow text-ink-muted line-through",
            )}
          >
            <span className="size-2.5 rounded-full" style={{ background: PLACE_KINDS[kind].color }} aria-hidden="true" />
            {PLACE_KINDS[kind].label}
          </button>
        );
      })}
    </div>
  );

  const details = selectedPlace ? (
    <PlaceCard
      place={selectedPlace}
      tours={selectedPlace.tours.map((s) => tours.get(s)).filter((t): t is MapTour => Boolean(t))}
      onTour={(slug) => setSelection({ type: "tour", slug })}
      onClose={() => setSelection(null)}
    />
  ) : selectedTour ? (
    <TourPanel
      tour={selectedTour}
      stops={selectedTour.stops.map((s) => places.get(s)).filter((p): p is MapPlace => Boolean(p))}
      onPlace={(slug) => setSelection({ type: "place", slug })}
      onClose={() => setSelection(null)}
    />
  ) : null;

  if (variant === "compact") {
    return (
      <div className="relative h-[460px] overflow-hidden rounded-3xl border border-line bg-lake-50 shadow-card">
        <div ref={container} className="size-full" />
        <MapToggles basemap={basemap} onBasemap={changeBasemap} terrain={terrain} onTerrain={changeTerrain} className="absolute top-3 left-3" />
        {details && <div className="absolute inset-x-3 bottom-3 max-h-[75%] overflow-y-auto sm:right-auto sm:w-96">{details}</div>}
      </div>
    );
  }

  return (
    <div className="grid lg:h-[calc(100dvh-72px)] lg:grid-cols-[380px_1fr]">
      <aside className="order-2 flex flex-col gap-4 overflow-y-auto border-line bg-snow p-4 lg:order-1 lg:border-r">
        <div>
          <h1 className="font-display text-2xl font-bold tracking-tight text-ink">Explore Kyrgyzstan</h1>
          <p className="mt-1 text-sm text-ink-muted">
            {data.places.length} places and {data.tours.length} tour routes. Tap a place to see the tours that stop there.
          </p>
        </div>
        {filters}
        {details ?? (
          <ul className="space-y-2">
            {data.tours.map((t) => (
              <li key={t.slug}>
                <TourRow tour={t} onSelect={() => setSelection({ type: "tour", slug: t.slug })} />
              </li>
            ))}
          </ul>
        )}
      </aside>
      <div className="relative order-1 h-[62dvh] lg:order-2 lg:h-auto">
        <div ref={container} className="size-full" />
        <MapToggles basemap={basemap} onBasemap={changeBasemap} terrain={terrain} onTerrain={changeTerrain} className="absolute top-3 left-3" />
      </div>
    </div>
  );
}

function TourRow({ tour, onSelect }: { tour: MapTour; onSelect: () => void }) {
  return (
    <button
      type="button"
      onClick={onSelect}
      className="flex w-full gap-3 rounded-xl border border-line bg-white p-2 text-left transition-shadow hover:shadow-card"
    >
      <span className="relative size-16 shrink-0 overflow-hidden rounded-lg bg-lake-50">
        {tour.image && <Image src={tour.image.src} alt="" fill sizes="64px" className="object-cover" />}
      </span>
      <span className="min-w-0">
        <span className="block truncate font-semibold text-ink">{tour.title}</span>
        <span className="block text-xs text-ink-muted">
          {tour.durationDays} {tour.durationDays === 1 ? "day" : "days"} · {tour.regionNames.join(", ")}
          {tour.distanceKm ? ` · ${Math.round(tour.distanceKm)} km` : ""}
        </span>
        {tour.priceFromCents !== null && (
          <span className="block text-xs text-ink-muted">
            from <Money cents={tour.priceFromCents} className="font-semibold text-ink" />
          </span>
        )}
      </span>
    </button>
  );
}

function Panel({ children, onClose }: { children: React.ReactNode; onClose: () => void }) {
  return (
    <div className="relative rounded-2xl border border-line bg-white p-4 shadow-card">
      <button
        type="button"
        onClick={onClose}
        aria-label="Close"
        className="absolute top-2 right-2 z-10 flex size-8 items-center justify-center rounded-full bg-white/90 text-ink-muted shadow-card hover:text-ink"
      >
        <X className="size-4" />
      </button>
      {children}
    </div>
  );
}

function PlaceCard({
  place,
  tours,
  onTour,
  onClose,
}: {
  place: MapPlace;
  tours: MapTour[];
  onTour: (slug: string) => void;
  onClose: () => void;
}) {
  return (
    <Panel onClose={onClose}>
      {place.photo && (
        <div className="relative -mx-4 -mt-4 mb-3 aspect-[16/9] overflow-hidden rounded-t-2xl">
          <Image src={place.photo.src} alt={place.photo.alt} fill sizes="380px" className="object-cover" />
        </div>
      )}
      <p className="flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase" style={{ color: PLACE_KINDS[place.kind].color }}>
        <MapPin className="size-3.5" aria-hidden="true" />
        {place.kindLabel}
        {place.region && <span className="text-ink-muted normal-case">· {place.region.name}</span>}
      </p>
      <h2 className="mt-1 pr-8 font-display text-xl font-bold text-ink">{place.name}</h2>
      {place.altitudeM && (
        <p className="mt-1 flex items-center gap-1 font-mono text-sm text-ink-muted">
          <Mountain className="size-4 text-lake" aria-hidden="true" />
          {place.altitudeM.toLocaleString("en-US")} m
        </p>
      )}
      {place.summary && <p className="mt-2 text-sm text-ink-muted">{place.summary}</p>}
      <div className="mt-4 border-t border-line pt-3">
        <p className="mb-2 text-sm font-semibold text-ink">
          {tours.length ? `Tours that stop here (${tours.length})` : "No tours stop here yet"}
        </p>
        <ul className="space-y-2">
          {tours.map((t) => (
            <li key={t.slug}>
              <TourRow tour={t} onSelect={() => onTour(t.slug)} />
            </li>
          ))}
        </ul>
        {!tours.length && (
          <Link href="/plan-my-trip" className="text-sm font-semibold text-lake hover:underline">
            Ask us to include it in a private trip →
          </Link>
        )}
      </div>
    </Panel>
  );
}

function TourPanel({
  tour,
  stops,
  onPlace,
  onClose,
}: {
  tour: MapTour;
  stops: MapPlace[];
  onPlace: (slug: string) => void;
  onClose: () => void;
}) {
  return (
    <Panel onClose={onClose}>
      {tour.image && (
        <div className="relative -mx-4 -mt-4 mb-3 aspect-[16/9] overflow-hidden rounded-t-2xl">
          <Image src={tour.image.src} alt={tour.image.alt} fill sizes="380px" className="object-cover" />
        </div>
      )}
      <h2 className="pr-8 font-display text-xl font-bold text-ink">{tour.title}</h2>
      <p className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-muted">
        <span className="flex items-center gap-1">
          <Clock className="size-4" aria-hidden="true" />
          {tour.durationDays} {tour.durationDays === 1 ? "day" : "days"}
        </span>
        {tour.distanceKm && (
          <span className="flex items-center gap-1">
            <Route className="size-4" aria-hidden="true" />
            {Math.round(tour.distanceKm)} km
          </span>
        )}
        <span>{tour.difficulty}</span>
      </p>
      <p className="mt-2 text-sm text-ink-muted">{tour.summary}</p>
      {stops.length > 0 && (
        <ol className="mt-3 flex flex-wrap gap-1.5">
          {stops.map((p, i) => (
            <li key={p.slug}>
              <button
                type="button"
                onClick={() => onPlace(p.slug)}
                className="flex items-center gap-1 rounded-md bg-snow px-2 py-1 text-xs font-semibold text-ink hover:bg-lake-50 hover:text-lake"
              >
                <span className="text-ink-muted">{i + 1}.</span> {p.name}
              </button>
            </li>
          ))}
        </ol>
      )}
      <div className="mt-4 flex items-center justify-between gap-3 border-t border-line pt-3">
        {tour.priceFromCents !== null ? (
          <p className="text-sm text-ink-muted">
            from <Money cents={tour.priceFromCents} className="text-lg font-bold text-ink" />
          </p>
        ) : (
          <span />
        )}
        <Link
          href={`/tours/${tour.slug}`}
          className="flex h-10 items-center gap-1.5 rounded-lg bg-kyrgyz px-4 text-sm font-semibold text-white hover:bg-kyrgyz-hover"
        >
          View tour
          <ArrowRight className="size-4" aria-hidden="true" />
        </Link>
      </div>
    </Panel>
  );
}
