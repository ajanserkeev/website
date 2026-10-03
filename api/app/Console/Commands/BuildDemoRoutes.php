<?php

namespace App\Console\Commands;

use App\Services\Maps\RouteBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Draws the demo tours' routes once from their routePlan and saves them into the demo catalog, so seeding
 * stays offline (CI has no network). Run again after changing a plan or the places in demo-catalog.json.
 */
#[Signature('demo:build-routes')]
#[Description('Calculate the routes of the demo tours (OSRM + elevation API) into demo-catalog.json')]
class BuildDemoRoutes extends Command
{
    public function handle(RouteBuilder $builder): int
    {
        $path = database_path('seeders/data/demo-catalog.json');
        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $places = collect($data['places'])->keyBy('slug');
        $built = 0;

        foreach ($data['tours'] as &$tour) {
            $plan = $tour['routePlan'] ?? null;
            if (! $plan) {
                continue;
            }
            $waypoints = array_map(function (array $w) use ($places) {
                if (isset($w['place'])) {
                    $place = $places[$w['place']];

                    return ['lng' => $place['lng'], 'lat' => $place['lat'], 'name' => $place['name']];
                }

                return $w;
            }, $plan['waypoints']);

            // The elevation API allows a limited number of points per minute.
            if ($built++ > 0) {
                sleep(15);
            }
            $tour['routeGeojson'] = $builder->build($waypoints, $plan['modes']);
            $props = $tour['routeGeojson']['properties'];
            $this->line(sprintf(
                '%s: %.1f km, +%s m%s',
                $tour['slug'],
                $props['distanceKm'],
                $props['elevationGainM'] ?? '?',
                $props['approximate'] ? ' (some segments straight: routing failed)' : '',
            ));
        }
        unset($tour);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // The file is indented with 2 spaces like the rest of the repository's JSON.
        $json = preg_replace_callback('/^( +)/m', fn ($m) => str_repeat(' ', intdiv(strlen($m[1]), 2)), $json);
        // Coordinate pairs and profile points on one line each.
        $json = preg_replace('/\[\s+(-?[\d.]+),\s+(-?[\d.]+)\s+\]/', '[$1, $2]', $json);
        file_put_contents($path, $json."\n");

        return self::SUCCESS;
    }
}
