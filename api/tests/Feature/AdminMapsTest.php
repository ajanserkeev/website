<?php

use App\Enums\PlaceKind;
use App\Enums\UserRole;
use App\Filament\Resources\Places\Pages\CreatePlace;
use App\Filament\Resources\Tours\Pages\EditTour;
use App\Models\Place;
use App\Models\Region;
use App\Models\Tour;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    config(['services.osrm.driving_url' => 'https://osrm.test', 'services.osrm.foot_url' => null, 'services.elevation.url' => null]);
    Http::preventStrayRequests();
});

it('opens the places pages and the route tab', function () {
    $this->seed(DemoCatalogSeeder::class);
    $place = Place::where('slug', 'song-kul-lake')->firstOrFail();
    $tour = Tour::where('slug', 'song-kul-horse-trek-yurt-stay')->firstOrFail();

    $this->get('/admin/places')->assertOk()->assertSee('Song-Kul Lake')->assertSee('Места на карте');
    $this->get("/admin/places/{$place->id}/edit")->assertOk()->assertSee('tuLocationPicker', false);
    $this->get("/admin/tours/{$tour->id}/edit")->assertOk()->assertSee('tuRouteBuilder', false)->assertSee('Собрать из дней программы');
});

it('creates a place', function () {
    $region = Region::factory()->create();

    Livewire::test(CreatePlace::class)
        ->fillForm([
            'name' => 'Kol-Ukok Lake',
            'kind' => PlaceKind::Lake,
            'region_id' => $region->id,
            'latitude' => 41.7930,
            'longitude' => 75.7820,
            'altitude_m' => 3040,
            'summary' => 'Lake above Kochkor.',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Place::where('slug', 'kol-ukok-lake')->firstOrFail())
        ->kind->toBe(PlaceKind::Lake)
        ->latitude->toBe(41.793)
        ->region_id->toBe($region->id);
});

it('rejects a point far outside Kyrgyzstan', function () {
    Livewire::test(CreatePlace::class)
        ->fillForm(['name' => 'Moscow', 'kind' => PlaceKind::Town, 'latitude' => 55.75, 'longitude' => 37.62])
        ->call('create')
        ->assertHasFormErrors(['latitude' => 'max', 'longitude' => 'min']);
});

it('builds and saves the route from the tour editor', function () {
    $this->seed(DemoCatalogSeeder::class);
    $tour = Tour::where('slug', 'bokonbaevo-eagle-hunters-skazka-canyon')->firstOrFail();
    Http::fake(['osrm.test/*' => Http::response([
        'routes' => [['distance' => 36000, 'geometry' => ['coordinates' => [[76.9962, 42.1141], [77.2, 42.15], [77.354, 42.1566]]]]],
        'waypoints' => [['distance' => 20], ['distance' => 40]],
    ])]);

    $page = Livewire::test(EditTour::class, ['record' => $tour->getRouteKey()]);
    $route = $page->instance()->buildTourRoute(
        [['lat' => 42.1141, 'lng' => 76.9962, 'name' => 'Bokonbaevo'], ['lat' => 42.1566, 'lng' => 77.354, 'name' => 'Skazka']],
        ['drive'],
    );
    expect($route['features'][0]['geometry']['coordinates'])->toBe([[76.9962, 42.1141], [77.2, 42.15], [77.354, 42.1566]]);

    $page->set('data.route_geojson', $route)->call('save')->assertHasNoFormErrors();

    expect($tour->fresh()->route_geojson['properties']['waypoints'][1]['name'])->toBe('Skazka');
});

it('returns errors to the route builder instead of failing', function () {
    $tour = Tour::factory()->create();
    $page = Livewire::test(EditTour::class, ['record' => $tour->getRouteKey()])->instance();

    expect($page->buildTourRoute([['lat' => 42.1, 'lng' => 77.0]], []))->toHaveKey('error')
        ->and($page->buildTourRoute([['lat' => 42.1, 'lng' => 77.0], ['lat' => 42.2, 'lng' => 77.1]], ['teleport']))->toHaveKey('error')
        ->and($page->importTourGpx('not a gpx'))->toBe(['error' => 'This is not a GPX file.']);
});
