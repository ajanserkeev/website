<?php

use App\Services\Maps\RouteBuilder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.osrm.driving_url' => 'https://osrm.test',
        'services.osrm.foot_url' => 'https://foot.test',
        'services.elevation.url' => 'https://elevation.test/v1/elevation',
    ]);
    Http::preventStrayRequests();
});

/** Elevation API double: heights rise 10 m per sampled point. */
function fakeElevation(): Closure
{
    return function (Request $request) {
        $count = count(explode(',', $request->data()['latitude']));

        return Http::response(['elevation' => array_map(fn ($i) => 2000 + $i * 10, range(0, $count - 1))]);
    };
}

const KARAKOL = ['lng' => 78.3936, 'lat' => 42.4907, 'name' => 'Karakol'];
const AK_SUU = ['lng' => 78.5224, 'lat' => 42.4997, 'name' => 'Ak-Suu'];
const ALTYN_ARASHAN = ['lng' => 78.6117, 'lat' => 42.3728, 'name' => 'Altyn-Arashan'];

it('follows roads and trails and adds the elevation profile', function () {
    Http::fake([
        'osrm.test/*' => Http::response([
            'routes' => [['distance' => 11900, 'geometry' => ['coordinates' => [[78.3937, 42.4906], [78.45, 42.51], [78.5223, 42.4996]]]]],
            'waypoints' => [['distance' => 12], ['distance' => 15]],
        ]),
        'foot.test/*' => Http::response([
            'routes' => [['distance' => 18000, 'geometry' => ['coordinates' => [[78.5224, 42.4997], [78.58, 42.43], [78.6117, 42.3728]]]]],
            'waypoints' => [['distance' => 5], ['distance' => 9]],
        ]),
        'elevation.test/*' => fakeElevation(),
    ]);

    $route = app(RouteBuilder::class)->build([KARAKOL, AK_SUU, ALTYN_ARASHAN], ['drive', 'hike']);

    expect($route['type'])->toBe('FeatureCollection')
        ->and($route['features'])->toHaveCount(2)
        ->and($route['features'][0]['properties']['mode'])->toBe('drive')
        // The road starts at the waypoint, not where OSRM snapped it.
        ->and($route['features'][0]['geometry']['coordinates'][0])->toBe([78.3936, 42.4907])
        ->and($route['features'][1]['properties']['mode'])->toBe('hike')
        ->and($route['properties']['approximate'])->toBeFalse()
        ->and($route['properties']['waypoints'][2]['name'])->toBe('Altyn-Arashan')
        ->and($route['properties']['distanceByMode'])->toHaveKeys(['drive', 'hike'])
        ->and($route['properties']['elevationProfile'])->toHaveCount(100)
        ->and($route['properties']['elevationGainM'])->toBe(990)
        ->and($route['properties']['maxAltitudeM'])->toBe(2990);

    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'osrm.test/route/v1/driving/78.3936,42.4907;78.5224,42.4997'));
});

it('draws a straight segment when routing fails or makes no sense', function (array $osrm) {
    Http::fake([
        'osrm.test/*' => Http::response(...$osrm),
        'elevation.test/*' => Http::response([], 500),
    ]);

    $route = app(RouteBuilder::class)->build([KARAKOL, AK_SUU], ['drive']);

    expect($route['features'][0]['geometry']['coordinates'])->toBe([[78.3936, 42.4907], [78.5224, 42.4997]])
        ->and($route['features'][0]['properties']['approximate'])->toBeTrue()
        ->and($route['properties']['approximate'])->toBeTrue()
        ->and($route['properties']['elevationProfile'])->toBeNull()
        ->and($route['properties']['elevationGainM'])->toBeNull();
})->with([
    'server error' => [[['message' => 'down'], 503]],
    'no route' => [[['code' => 'NoRoute', 'routes' => []]]],
    'point far from any road' => [[[
        'routes' => [['distance' => 12000, 'geometry' => ['coordinates' => [[78.39, 42.49], [78.52, 42.49]]]]],
        'waypoints' => [['distance' => 8000], ['distance' => 10]],
    ]]],
    'detour around the range' => [[[
        'routes' => [['distance' => 90000, 'geometry' => ['coordinates' => [[78.39, 42.49], [78.52, 42.49]]]]],
        'waypoints' => [['distance' => 10], ['distance' => 10]],
    ]]],
]);

it('keeps straight lines without calling a router', function () {
    config(['services.osrm.foot_url' => null]);
    Http::fake(['elevation.test/*' => fakeElevation()]);

    $route = app(RouteBuilder::class)->build([AK_SUU, ALTYN_ARASHAN, KARAKOL], ['horse', 'line']);

    expect(collect($route['features'])->pluck('properties.approximate')->all())->toBe([false, false])
        ->and($route['properties']['distanceKm'])->toBeGreaterThan(25);
    Http::assertSentCount(1);
});

it('rejects routes it cannot build', function (array $waypoints, array $modes) {
    app(RouteBuilder::class)->build($waypoints, $modes);
})->throws(InvalidArgumentException::class)->with([
    'one point' => [[KARAKOL], []],
    'missing mode' => [[KARAKOL, AK_SUU, ALTYN_ARASHAN], ['drive']],
    'unknown mode' => [[KARAKOL, AK_SUU], ['ski']],
]);

it('imports a GPX track with its own heights', function (string $namespace) {
    $points = collect([[42.4997, 78.5224, 1800], [42.45, 78.55, 2100], [42.42, 78.58, 2050], [42.3728, 78.6117, 2600]])
        ->map(fn ($p) => "<trkpt lat=\"{$p[0]}\" lon=\"{$p[1]}\"><ele>{$p[2]}</ele><time>2026-07-01T08:00:00Z</time></trkpt>")
        ->implode('');
    $gpx = "<?xml version=\"1.0\"?><gpx version=\"1.1\" {$namespace}><trk><name>Ak-Suu</name><trkseg>{$points}</trkseg></trk></gpx>";

    $route = app(RouteBuilder::class)->fromGpx($gpx);

    expect($route['properties']['source'])->toBe('gpx')
        ->and($route['features'][0]['properties']['mode'])->toBe('hike')
        ->and($route['features'][0]['geometry']['coordinates'])->toHaveCount(4)
        ->and($route['properties']['elevationGainM'])->toBe(850)
        ->and($route['properties']['elevationLossM'])->toBe(50)
        ->and($route['properties']['maxAltitudeM'])->toBe(2600)
        ->and($route['properties']['waypoints'])->toHaveCount(2);
    Http::assertNothingSent();
})->with([
    'GPX 1.1' => ['xmlns="http://www.topografix.com/GPX/1/1"'],
    'GPX 1.0' => ['xmlns="http://www.topografix.com/GPX/1/0"'],
    'no namespace' => [''],
]);

it('refuses files that are not GPX tracks', function (string $xml) {
    app(RouteBuilder::class)->fromGpx($xml);
})->throws(InvalidArgumentException::class)->with([
    'not xml' => ['hello'],
    'no track' => ['<gpx xmlns="http://www.topografix.com/GPX/1/1"><wpt lat="42" lon="78"/></gpx>'],
]);
