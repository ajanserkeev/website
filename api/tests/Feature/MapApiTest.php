<?php

use App\Enums\TourStatus;
use App\Models\Place;
use App\Models\Tour;
use Database\Seeders\DemoCatalogSeeder;

beforeEach(fn () => $this->seed(DemoCatalogSeeder::class));

it('lists published places and tour lines for the explore map', function () {
    Place::factory()->create(['name' => 'Hidden Lake', 'is_published' => false]);
    Tour::where('slug', 'kel-suu-lake-expedition')->update(['status' => TourStatus::Draft]);

    $response = $this->getJson('/api/v1/map')->assertOk()
        ->assertJsonMissing(['name' => 'Hidden Lake'])
        ->assertJsonMissing(['slug' => 'kel-suu-lake-expedition']);

    $places = collect($response->json('data.places'))->keyBy('slug');
    $tours = collect($response->json('data.tours'))->keyBy('slug');

    expect($places)->toHaveCount(31)
        ->and($places['song-kul-lake'])->toMatchArray(['kind' => 'lake', 'kindLabel' => 'Lake', 'altitudeM' => 3016, 'featured' => true])
        ->and($places['song-kul-lake']['region'])->toBe(['slug' => 'naryn', 'name' => 'Naryn & Song-Kul'])
        ->and($places['song-kul-lake']['tours'])->toContain('song-kul-horse-trek-yurt-stay', 'kyrgyzstan-highlights-lakes-tash-rabat')
        // The draft tour no longer counts for its places.
        ->and($places['kel-suu-lake']['tours'])->toBe([])
        ->and($tours)->toHaveCount(6)
        ->and(count($tours['song-kul-horse-trek-yurt-stay']['line']))->toBeLessThanOrEqual(151)
        ->and($tours['song-kul-horse-trek-yurt-stay']['stops'])->toBe(['kilemche-jailoo', 'song-kul-lake', 'kochkor'])
        ->and($tours['song-kul-horse-trek-yurt-stay'])->toHaveKeys(['title', 'priceFromCents', 'image', 'distanceKm']);
});

it('gives the tour page its route and numbered day stops', function () {
    $response = $this->getJson('/api/v1/tours/ala-kul-lake-trek-from-karakol')->assertOk();

    expect($response->json('data.map.route.type'))->toBe('FeatureCollection')
        ->and($response->json('data.map.route.properties.elevationProfile'))->not->toBeEmpty()
        ->and(collect($response->json('data.map.stops'))->pluck('day')->all())->toBe([1, 2, 3, 4])
        ->and($response->json('data.map.stops.1'))->toMatchArray(['slug' => 'ala-kul-lake', 'name' => 'Ala-Kul Lake', 'altitudeM' => 3530])
        ->and($response->json('data.days.1.place'))->toBe(['slug' => 'ala-kul-lake', 'name' => 'Ala-Kul Lake']);
});

it('falls back to the stops when a tour has no drawn route', function () {
    $tour = Tour::where('slug', 'bokonbaevo-eagle-hunters-skazka-canyon')->firstOrFail();
    $tour->update(['route_geojson' => null]);

    $this->getJson("/api/v1/tours/{$tour->slug}")->assertJsonPath('data.map.route', null);
    $line = collect($this->getJson('/api/v1/map')->json('data.tours'))->firstWhere('slug', $tour->slug)['line'];

    // Bokonbaevo (days 1–2) and Skazka canyon (day 3).
    expect($line)->toBe([[76.9962, 42.1141], [77.354, 42.1566]]);
});

it('hides unpublished places from tour stops', function () {
    Place::where('slug', 'kochkor')->update(['is_published' => false]);

    $this->getJson('/api/v1/tours/song-kul-horse-trek-yurt-stay')
        ->assertJsonCount(2, 'data.map.stops')
        ->assertJsonPath('data.days.2.place', null);
});
