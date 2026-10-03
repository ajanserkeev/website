<?php

namespace App\Services\Maps;

use App\Models\Place;
use App\Models\Tour;
use App\Models\TourDay;

/** Map data of a tour for the public API: the drawn route and the day stops. Needs days.place loaded. */
final class TourMap
{
    /** @return array{route: ?array, stops: list<array>}|null null when the tour has nothing to show on a map */
    public static function forTour(Tour $tour): ?array
    {
        $stops = self::stops($tour);
        $route = self::route($tour);

        return $route || $stops ? ['route' => $route, 'stops' => $stops] : null;
    }

    /**
     * Thin line for overview maps: the route, or the stops joined in order when no route is drawn.
     *
     * @return list<array{0: float, 1: float}>|null
     */
    public static function line(Tour $tour, int $maxPoints = 150): ?array
    {
        $route = self::route($tour);
        $line = [];
        foreach ($route['features'] ?? [] as $feature) {
            array_push($line, ...($feature['geometry']['coordinates'] ?? []));
        }
        if (count($line) < 2) {
            // Several days at the same place are one point.
            $line = array_values(array_unique(array_map(fn ($s) => [$s['lng'], $s['lat']], self::stops($tour)), SORT_REGULAR));
        }
        if (count($line) < 2) {
            return null;
        }

        $step = max(1, (int) ceil(count($line) / $maxPoints));
        $last = array_key_last($line);
        $thin = [];
        foreach ($line as $i => $point) {
            if ($i % $step === 0 || $i === $last) {
                $thin[] = [round($point[0], 4), round($point[1], 4)];
            }
        }

        return $thin;
    }

    /** @return list<array> One per day that ends at a published place. */
    public static function stops(Tour $tour): array
    {
        return $tour->days
            ->filter(fn (TourDay $d) => $d->place?->is_published)
            ->map(fn (TourDay $d) => ['day' => $d->day_number, ...self::place($d->place)])
            ->values()
            ->all();
    }

    public static function place(Place $place): array
    {
        return [
            'slug' => $place->slug,
            'name' => $place->name,
            'kind' => $place->kind->value,
            'kindLabel' => $place->kind->label(),
            'lng' => round($place->longitude, 5),
            'lat' => round($place->latitude, 5),
            'altitudeM' => $place->altitude_m,
        ];
    }

    private static function route(Tour $tour): ?array
    {
        $route = $tour->route_geojson;

        return is_array($route) && ($route['type'] ?? null) === 'FeatureCollection' && ! empty($route['features']) ? $route : null;
    }
}
