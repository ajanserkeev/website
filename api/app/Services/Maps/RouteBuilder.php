<?php

namespace App\Services\Maps;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Builds the route drawn on a tour map (ported from the GO-Kyrgyzstan prototype's TourRouteService).
 *
 * The result is a GeoJSON FeatureCollection stored in tours.route_geojson: one LineString per segment
 * between waypoints, with its mode, plus totals and an elevation profile in the collection properties.
 * - drive: follows roads via OSRM (OSRM_DRIVING_URL, the public demo server by default);
 * - hike, horse: follows paths via an OSRM foot server (OSRM_FOOT_URL, FOSSGIS by default); many mountain
 *   trails are missing in OpenStreetMap, then the segment is straight: add waypoints or import a GPX track;
 * - line: always straight.
 * If routing fails the segment is drawn straight and marked approximate, so the editor never gets stuck.
 */
final class RouteBuilder
{
    public const MODES = ['drive', 'hike', 'horse', 'line'];

    private const PROFILE_POINTS = 120;

    private const MAX_POINTS_PER_SEGMENT = 400;

    private const MAX_GPX_POINTS = 1500;

    /** Average speeds for the duration estimate, km/h. */
    private const SPEEDS = ['drive' => 50, 'hike' => 3.5, 'horse' => 6, 'line' => 50];

    /**
     * @param  list<array{lng: float|int|string, lat: float|int|string, name?: ?string}>  $waypoints
     * @param  list<string>  $modes  one per segment, so count($waypoints) - 1
     */
    public function build(array $waypoints, array $modes): array
    {
        $waypoints = array_map(fn (array $w) => [
            'lng' => round((float) $w['lng'], 6),
            'lat' => round((float) $w['lat'], 6),
            'name' => isset($w['name']) && $w['name'] !== '' ? (string) $w['name'] : null,
        ], array_values($waypoints));

        if (count($waypoints) < 2) {
            throw new InvalidArgumentException('A route needs at least two points.');
        }
        $modes = array_values($modes);
        if (count($modes) !== count($waypoints) - 1) {
            throw new InvalidArgumentException('Every segment needs a mode.');
        }

        $features = [];
        foreach ($modes as $i => $mode) {
            if (! in_array($mode, self::MODES, true)) {
                throw new InvalidArgumentException("Unknown mode {$mode}.");
            }
            [$coordinates, $approximate] = $this->segment($waypoints[$i], $waypoints[$i + 1], $mode);
            $features[] = $this->feature($coordinates, $mode, $approximate);
        }

        $elevations = $this->elevations($features);

        return $this->collection($features, $waypoints, 'builder', $elevations);
    }

    /** Imports a GPX track (trkpt, else rtept) as one hiking segment with the elevations from the file. */
    public function fromGpx(string $xml, string $mode = 'hike'): array
    {
        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, options: LIBXML_NONET);
        libxml_use_internal_errors($previous);
        if ($doc === false) {
            throw new InvalidArgumentException('This is not a GPX file.');
        }

        // GPX 1.0, 1.1 and files without a namespace all name the elements the same.
        $points = $doc->xpath("//*[local-name()='trkpt']") ?: $doc->xpath("//*[local-name()='rtept']") ?: [];
        if (count($points) < 2) {
            throw new InvalidArgumentException('The GPX file has no track.');
        }

        $coordinates = [];
        $heights = [];
        foreach ($points as $point) {
            $coordinates[] = [round((float) $point['lon'], 5), round((float) $point['lat'], 5)];
            $ele = $point->xpath("*[local-name()='ele']")[0] ?? null;
            $heights[] = $ele !== null && trim((string) $ele) !== '' ? (float) $ele : null;
        }

        $features = [$this->feature($this->downsample($coordinates, self::MAX_GPX_POINTS), in_array($mode, self::MODES, true) ? $mode : 'hike', false)];
        $first = $coordinates[0];
        $last = $coordinates[array_key_last($coordinates)];
        $waypoints = [
            ['lng' => $first[0], 'lat' => $first[1], 'name' => 'Start'],
            ['lng' => $last[0], 'lat' => $last[1], 'name' => 'Finish'],
        ];

        $known = array_values(array_filter($heights, fn ($h) => $h !== null));
        if (count($known) < 2) {
            return $this->collection($features, $waypoints, 'gpx', $this->elevations($features));
        }

        // Climb is counted on the full track; the stored profile is thinned for the chart.
        return $this->collection($features, $waypoints, 'gpx', $this->profileFromPoints($coordinates, $heights), $this->climb(array_map('intval', array_map('round', $known))));
    }

    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** @return array{0: list<array{0: float, 1: float}>, 1: bool} coordinates and whether they are a fallback */
    private function segment(array $from, array $to, string $mode): array
    {
        $straight = [[$from['lng'], $from['lat']], [$to['lng'], $to['lat']]];

        $server = match ($mode) {
            'drive' => [config('services.osrm.driving_url'), 'driving'],
            'hike', 'horse' => [config('services.osrm.foot_url'), 'foot'],
            default => [null, null],
        };
        if (! $server[0]) {
            return [$straight, false];
        }

        $coordinates = $this->osrm($server[0], $server[1], $from, $to);

        return $coordinates ? [$coordinates, false] : [$straight, true];
    }

    /**
     * Road or trail between two points, or null when OSRM has none that makes sense: a point far from any
     * road/path (OSRM then starts the route somewhere else) or a detour much longer than the straight line
     * (the trail is missing in OpenStreetMap and OSRM goes round the range).
     *
     * @return list<array{0: float, 1: float}>|null
     */
    private function osrm(string $baseUrl, string $profile, array $from, array $to): ?array
    {
        $url = rtrim($baseUrl, '/')."/route/v1/{$profile}/{$from['lng']},{$from['lat']};{$to['lng']},{$to['lat']}";
        [$maxSnapM, $maxDetour] = $profile === 'driving' ? [3000, 4.0] : [1500, 2.2];

        try {
            $response = Http::timeout(8)
                ->withUserAgent(config('brand.name').' route builder')
                ->get($url, ['overview' => 'full', 'geometries' => 'geojson', 'steps' => 'false']);
            $coordinates = $response->json('routes.0.geometry.coordinates');
            if (! $response->successful() || ! is_array($coordinates) || count($coordinates) < 2) {
                return null;
            }

            $snaps = array_map(fn ($w) => (float) ($w['distance'] ?? 0), $response->json('waypoints') ?? []);
            $straightKm = self::haversineKm($from['lat'], $from['lng'], $to['lat'], $to['lng']);
            $routedKm = (float) $response->json('routes.0.distance') / 1000;
            if (($snaps && max($snaps) > $maxSnapM) || ($straightKm > 0.5 && $routedKm > $straightKm * $maxDetour)) {
                Log::info('OSRM route rejected', ['url' => $url, 'snaps_m' => $snaps, 'km' => $routedKm, 'straight_km' => $straightKm]);

                return null;
            }

            $line = array_map(fn ($c) => [round((float) $c[0], 5), round((float) $c[1], 5)], $coordinates);

            // OSRM starts and ends on the nearest road; join it to the waypoints themselves.
            return [[$from['lng'], $from['lat']], ...$line, [$to['lng'], $to['lat']]];
        } catch (Throwable $e) {
            Log::info('OSRM routing failed, drawing a straight line', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function feature(array $coordinates, string $mode, bool $approximate): array
    {
        $coordinates = $this->downsample($this->simplify($coordinates, 0.0002), self::MAX_POINTS_PER_SEGMENT);

        return [
            'type' => 'Feature',
            'properties' => [
                'mode' => $mode,
                'distanceKm' => round($this->lengthKm($coordinates), 1),
                'approximate' => $approximate,
            ],
            'geometry' => ['type' => 'LineString', 'coordinates' => $coordinates],
        ];
    }

    /**
     * Heights along the whole route from the elevation API (Open-Meteo, Copernicus DEM 90 m), sampled
     * at PROFILE_POINTS evenly spaced points. Null when the API is down: the map works without a profile.
     *
     * @return list<array{0: float, 1: int}>|null [km from start, metres]
     */
    private function elevations(array $features): ?array
    {
        $url = config('services.elevation.url');
        if (! $url) {
            return null;
        }

        $line = [];
        foreach ($features as $feature) {
            array_push($line, ...$feature['geometry']['coordinates']);
        }
        $samples = $this->samplesAlong($line, min(self::PROFILE_POINTS, 100));
        if (count($samples) < 2) {
            return null;
        }

        try {
            $response = Http::timeout(8)->get($url, [
                'latitude' => implode(',', array_map(fn ($s) => $s[1][1], $samples)),
                'longitude' => implode(',', array_map(fn ($s) => $s[1][0], $samples)),
            ]);
            $heights = $response->json('elevation');
            if (! $response->successful() || ! is_array($heights) || count($heights) !== count($samples)) {
                return null;
            }
        } catch (Throwable $e) {
            Log::info('Elevation lookup failed', ['error' => $e->getMessage()]);

            return null;
        }

        return array_map(fn ($sample, $height) => [round($sample[0], 2), (int) round((float) $height)], $samples, $heights);
    }

    /**
     * @param  list<array{0: float, 1: float}>  $coordinates
     * @param  list<float|null>  $heights
     * @return list<array{0: float, 1: int}>
     */
    private function profileFromPoints(array $coordinates, array $heights): array
    {
        $profile = [];
        $km = 0.0;
        foreach ($coordinates as $i => $c) {
            if ($i > 0) {
                $km += self::haversineKm($coordinates[$i - 1][1], $coordinates[$i - 1][0], $c[1], $c[0]);
            }
            if ($heights[$i] !== null) {
                $profile[] = [round($km, 2), (int) round($heights[$i])];
            }
        }

        return $this->downsample($profile, self::PROFILE_POINTS);
    }

    /**
     * Points spaced evenly by distance along a line, with their distance from the start.
     *
     * @return list<array{0: float, 1: array{0: float, 1: float}}>
     */
    private function samplesAlong(array $line, int $count): array
    {
        $total = $this->lengthKm($line);
        if ($total <= 0 || $count < 2) {
            return [];
        }

        $samples = [[0.0, $line[0]]];
        $step = $total / ($count - 1);
        $target = $step;
        $walked = 0.0;
        for ($i = 1; $i < count($line) && count($samples) < $count - 1; $i++) {
            [$a, $b] = [$line[$i - 1], $line[$i]];
            $piece = self::haversineKm($a[1], $a[0], $b[1], $b[0]);
            while ($piece > 0 && $walked + $piece >= $target && count($samples) < $count - 1) {
                $t = ($target - $walked) / $piece;
                $samples[] = [$target, [round($a[0] + ($b[0] - $a[0]) * $t, 5), round($a[1] + ($b[1] - $a[1]) * $t, 5)]];
                $target += $step;
            }
            $walked += $piece;
        }
        $samples[] = [$total, $line[array_key_last($line)]];

        return $samples;
    }

    /**
     * @param  list<array{0: float, 1: int}>|null  $profile
     * @param  array{0: int, 1: int}|null  $climb  when known more precisely than from the profile
     */
    private function collection(array $features, array $waypoints, string $source, ?array $profile, ?array $climb = null): array
    {
        $byMode = [];
        $hours = 0.0;
        foreach ($features as $feature) {
            $mode = $feature['properties']['mode'];
            $km = $feature['properties']['distanceKm'];
            $byMode[$mode] = round(($byMode[$mode] ?? 0) + $km, 1);
            $hours += $km / self::SPEEDS[$mode];
        }

        [$gain, $loss] = $climb ?? ($profile ? $this->climb(array_column($profile, 1)) : [null, null]);
        $heights = $profile ? array_column($profile, 1) : [];

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
            'properties' => [
                'source' => $source,
                'waypoints' => $waypoints,
                'distanceKm' => round(array_sum($byMode), 1),
                'distanceByMode' => $byMode,
                'durationHours' => round($hours, 1),
                'elevationGainM' => $gain,
                'elevationLossM' => $loss,
                'maxAltitudeM' => $heights ? max($heights) : null,
                'minAltitudeM' => $heights ? min($heights) : null,
                'elevationProfile' => $profile,
                'approximate' => collect($features)->contains(fn ($f) => $f['properties']['approximate']),
            ],
        ];
    }

    /**
     * Total climb and descent; changes under 5 m are treated as noise.
     *
     * @param  list<int>  $heights
     * @return array{0: int, 1: int}
     */
    private function climb(array $heights): array
    {
        $gain = $loss = 0;
        $reference = $heights[0];
        foreach ($heights as $h) {
            $delta = $h - $reference;
            if (abs($delta) >= 5) {
                $delta > 0 ? $gain += $delta : $loss -= $delta;
                $reference = $h;
            }
        }

        return [$gain, $loss];
    }

    private function lengthKm(array $line): float
    {
        $km = 0.0;
        for ($i = 1; $i < count($line); $i++) {
            $km += self::haversineKm($line[$i - 1][1], $line[$i - 1][0], $line[$i][1], $line[$i][0]);
        }

        return $km;
    }

    /**
     * Douglas–Peucker: drops points closer than $tolerance degrees (0.0002 ≈ 20 m) to the simplified line,
     * so a road keeps its bends at a fraction of OSRM's points.
     */
    private function simplify(array $points, float $tolerance): array
    {
        $count = count($points);
        if ($count < 3) {
            return $points;
        }

        $keep = array_fill(0, $count, false);
        $keep[0] = $keep[$count - 1] = true;
        $stack = [[0, $count - 1]];
        while ($stack) {
            [$first, $last] = array_pop($stack);
            [$ax, $ay] = $points[$first];
            [$bx, $by] = $points[$last];
            $dx = $bx - $ax;
            $dy = $by - $ay;
            $length = $dx * $dx + $dy * $dy;
            $farthest = null;
            $max = $tolerance * $tolerance;
            for ($i = $first + 1; $i < $last; $i++) {
                [$px, $py] = $points[$i];
                $t = $length > 0 ? max(0, min(1, (($px - $ax) * $dx + ($py - $ay) * $dy) / $length)) : 0;
                $distance = ($px - $ax - $t * $dx) ** 2 + ($py - $ay - $t * $dy) ** 2;
                if ($distance > $max) {
                    [$max, $farthest] = [$distance, $i];
                }
            }
            if ($farthest !== null) {
                $keep[$farthest] = true;
                $stack[] = [$first, $farthest];
                $stack[] = [$farthest, $last];
            }
        }

        return array_values(array_filter($points, fn ($i) => $keep[$i], ARRAY_FILTER_USE_KEY));
    }

    /** Keeps every n-th point and always the last one. */
    private function downsample(array $points, int $max): array
    {
        $count = count($points);
        if ($count <= $max) {
            return array_values($points);
        }

        $step = ($count - 1) / ($max - 1);
        $result = [];
        for ($i = 0; $i < $max - 1; $i++) {
            $result[] = $points[(int) floor($i * $step)];
        }
        $result[] = $points[$count - 1];

        return $result;
    }
}
