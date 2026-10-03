<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Photo;
use App\Http\Resources\V1\TourSummaryResource;
use App\Models\Place;
use App\Models\Tour;
use App\Services\Catalog\TourCatalog;
use App\Services\Maps\TourMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Everything the "Explore the map" page draws: places of interest and the routes of published tours. */
class MapController extends Controller
{
    public function __invoke(Request $request, TourCatalog $catalog): JsonResponse
    {
        $tours = $catalog->query()->with('days.place')->orderByDesc('sort_weight')->get();

        // Which tours pass each place, from their day stops.
        $toursByPlace = [];
        foreach ($tours as $tour) {
            foreach ($tour->days as $day) {
                if ($day->place_id && ! in_array($tour->slug, $toursByPlace[$day->place_id] ?? [], true)) {
                    $toursByPlace[$day->place_id][] = $tour->slug;
                }
            }
        }

        $places = Place::query()->published()->with(['region', 'media'])->orderBy('sort')->orderBy('name')->get();

        return response()->json(['data' => [
            'places' => $places->map(fn (Place $p) => [
                ...TourMap::place($p),
                'summary' => $p->summary,
                'featured' => $p->is_featured,
                'region' => $p->region ? ['slug' => $p->region->slug, 'name' => $p->region->name] : null,
                'photo' => Photo::first($p, 'photo', $p->name),
                'tours' => $toursByPlace[$p->id] ?? [],
            ])->values(),
            'tours' => $tours->map(fn (Tour $t) => [
                ...TourSummaryResource::make($t)->resolve($request),
                'line' => TourMap::line($t),
                'stops' => collect(TourMap::stops($t))->map(fn ($s) => $s['slug'])->unique()->values(),
                'distanceKm' => $t->route_geojson['properties']['distanceKm'] ?? null,
            ])->filter(fn ($t) => $t['line'] !== null)->values(),
        ]]);
    }
}
