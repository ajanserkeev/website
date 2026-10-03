"use client";

import dynamic from "next/dynamic";
import { useEffect, useRef, useState } from "react";
import type { ExploreMapData, TourMapData } from "@/lib/types";

function MapPlaceholder({ className }: { className: string }) {
  return (
    <div className={`flex items-center justify-center bg-lake-50 text-sm font-semibold text-lake ${className}`} aria-hidden="true">
      Loading map…
    </div>
  );
}

// MapLibre needs the browser (WebGL), so the maps render on the client only.
const ExploreMap = dynamic(() => import("./explore-map"), {
  ssr: false,
  loading: () => <MapPlaceholder className="h-[62dvh] lg:h-[calc(100dvh-72px)]" />,
});
const CompactExploreMap = dynamic(() => import("./explore-map"), {
  ssr: false,
  loading: () => <MapPlaceholder className="h-[460px] rounded-3xl" />,
});
const TourRouteMap = dynamic(() => import("./tour-route-map"), {
  ssr: false,
  loading: () => <MapPlaceholder className="h-[420px] rounded-2xl sm:h-[480px]" />,
});

/** Loads the map code only when the block scrolls near the screen (the map library is large). */
function WhenVisible({ children, placeholder }: { children: React.ReactNode; placeholder: React.ReactNode }) {
  const ref = useRef<HTMLDivElement>(null);
  const [visible, setVisible] = useState(false);
  useEffect(() => {
    const element = ref.current;
    if (!element) return;
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setVisible(true);
          observer.disconnect();
        }
      },
      { rootMargin: "400px" },
    );
    observer.observe(element);
    return () => observer.disconnect();
  }, []);
  return <div ref={ref}>{visible ? children : placeholder}</div>;
}

export function ExploreMapPage({ data, initialPlace }: { data: ExploreMapData; initialPlace?: string }) {
  return <ExploreMap data={data} initialPlace={initialPlace} />;
}

export function ExploreMapBlock({ data }: { data: ExploreMapData }) {
  return (
    <WhenVisible placeholder={<MapPlaceholder className="h-[460px] rounded-3xl" />}>
      <CompactExploreMap data={data} variant="compact" />
    </WhenVisible>
  );
}

export function TourRouteMapBlock({ data, maxAltitudeM }: { data: TourMapData; maxAltitudeM: number | null }) {
  return (
    <WhenVisible placeholder={<MapPlaceholder className="h-[420px] rounded-2xl sm:h-[480px]" />}>
      <TourRouteMap data={data} maxAltitudeM={maxAltitudeM} />
    </WhenVisible>
  );
}
