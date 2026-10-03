<?php

namespace App\Filament\Forms\Components;

use App\Models\Place;
use Filament\Forms\Components\Field;

/**
 * Map editor of tours.route_geojson (ported from the GO-Kyrgyzstan prototype's route builder):
 * click to add waypoints, choose how each segment is travelled, build from the itinerary's places or
 * import a GPX track. The page computes the route through BuildsTourRoute.
 */
class RouteBuilder extends Field
{
    protected string $view = 'filament.forms.components.route-builder';

    /** @return list<array{id: int, name: string, lat: float, lng: float}> */
    public function getPlaces(): array
    {
        return Place::query()->orderBy('name')->get(['id', 'name', 'latitude', 'longitude'])
            ->map(fn (Place $p) => ['id' => $p->id, 'name' => $p->name, 'lat' => $p->latitude, 'lng' => $p->longitude])
            ->all();
    }
}
