<?php

use App\Enums\DepartureStatus;
use App\Enums\TourStatus;
use App\Models\Departure;
use App\Models\Operator;
use App\Models\Tour;
use Database\Seeders\DemoCatalogSeeder;

beforeEach(fn () => $this->seed(DemoCatalogSeeder::class));

it('lists published tours of active operators as cards', function () {
    Tour::factory()->create(['status' => TourStatus::Draft, 'title' => 'Hidden draft']);
    Tour::factory()->for(Operator::factory()->create(['is_active' => false]))->create(['title' => 'Inactive operator']);

    $this->getJson('/api/v1/tours')
        ->assertOk()
        ->assertJsonCount(7, 'data')
        ->assertJsonPath('meta.total', 7)
        ->assertJsonMissing(['title' => 'Hidden draft'])
        ->assertJsonMissing(['title' => 'Inactive operator'])
        ->assertJsonStructure(['data' => [['slug', 'title', 'image', 'priceFromCents', 'depositFromCents', 'rating', 'badges']]]);
});

it('filters and sorts the catalog like the site', function (string $query, array $slugs) {
    $response = $this->getJson("/api/v1/tours?{$query}")->assertOk();

    expect(collect($response->json('data'))->pluck('slug')->all())->toBe($slugs);
})->with([
    'activity' => ['activity=horse-riding', ['song-kul-horse-trek-yurt-stay']],
    'no tours in december' => ['month=2027-12', []],
    'region and duration' => ['region=naryn&duration=2-4', ['song-kul-horse-trek-yurt-stay']],
    'cheap' => ['maxPrice=300&sort=price-asc', ['ala-archa-national-park-hike', 'bokonbaevo-eagle-hunters-skazka-canyon']],
    'by slugs' => ['slugs[]=lenin-peak-base-camp-alay-valley&slugs[]=kel-suu-lake-expedition', ['kel-suu-lake-expedition', 'lenin-peak-base-camp-alay-valley']],
]);

it('rejects unknown filter values', function () {
    $this->getJson('/api/v1/tours?duration=forever')->assertUnprocessable();
});

it('returns a full tour without operator contacts', function () {
    $tour = Tour::where('slug', 'song-kul-horse-trek-yurt-stay')->firstOrFail();
    $tour->operator->update(['phone' => '+996 555 111 222', 'whatsapp' => '+996 700 333 444', 'email' => 'secret@operator.kg']);

    $response = $this->getJson('/api/v1/tours/song-kul-horse-trek-yurt-stay')
        ->assertOk()
        ->assertJsonPath('data.priceFromCents', 33000)
        ->assertJsonPath('data.depositFromCents', 5000)
        ->assertJsonPath('data.days.1.title', 'Jalgyz Karagai pass → Song-Kul')
        ->assertJsonPath('data.rating.source', 'verified reviews');

    expect($response->getContent())->not->toContain('+996 555 111 222')
        ->not->toContain('+996 700 333 444')
        ->not->toContain('secret@operator.kg')
        ->and($response->json('data.operator'))->not->toHaveKeys(['phone', 'whatsapp', 'email', 'contactName']);
});

it('hides drafts and unknown slugs', function () {
    Tour::where('slug', 'kel-suu-lake-expedition')->update(['status' => TourStatus::Draft]);

    $this->getJson('/api/v1/tours/kel-suu-lake-expedition')->assertNotFound();
    $this->getJson('/api/v1/tours/no-such-tour')->assertNotFound();
});

it('derives badges from real seats only', function () {
    $tour = Tour::factory()->create(['group_size_max' => 12]);
    Departure::factory()->for($tour)->create(['seats_total' => 10, 'seats_booked' => 9]);
    Departure::factory()->for($tour)->create(['seats_total' => 10, 'seats_booked' => 10, 'status' => DepartureStatus::Full]);

    $badges = collect($this->getJson("/api/v1/tours/{$tour->slug}")->json('data.badges'))->pluck('label');

    expect($badges->all())->toBe(['Only 1 spot left']);
});

it('serves regions, collections, guides, reviews and rates', function () {
    $this->getJson('/api/v1/regions')->assertOk()->assertJsonPath('data.0.slug', 'naryn')->assertJsonPath('data.0.tourCount', 3);
    $this->getJson('/api/v1/collections?featured=1')->assertOk()->assertJsonCount(4, 'data')->assertJsonPath('data.0.tours.0.slug', 'song-kul-horse-trek-yurt-stay');
    $this->getJson('/api/v1/posts/song-kul-lake-guide')->assertOk()->assertJsonCount(2, 'data.tours')->assertJsonCount(6, 'data.sections');
    $this->getJson('/api/v1/reviews/featured')->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.0.verifiedBooking', false);
    $this->getJson('/api/v1/currency-rates')->assertOk()->assertJsonPath('data.EUR', 0.92);
});
