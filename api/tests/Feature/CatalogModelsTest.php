<?php

use App\Enums\DepartureStatus;
use App\Enums\TourItemKind;
use App\Models\Departure;
use App\Models\Operator;
use App\Models\PrivatePrice;
use App\Models\Region;
use App\Models\Tour;
use Database\Seeders\DemoCatalogSeeder;

it('seeds the demo catalog shared with the web app', function () {
    $this->seed(DemoCatalogSeeder::class);

    $tour = Tour::where('slug', 'song-kul-horse-trek-yurt-stay')->firstOrFail();

    expect(Operator::count())->toBe(6)
        ->and(Tour::published()->count())->toBe(7)
        ->and($tour->operator->name)->toBe('Arstan Horse Trails')
        ->and($tour->days->pluck('day_number')->all())->toBe([1, 2, 3])
        ->and($tour->includedItems)->toHaveCount(4)
        ->and($tour->excludedItems)->toHaveCount(3)
        ->and($tour->departures)->toHaveCount(4)
        ->and($tour->regions->pluck('slug')->all())->toBe(['naryn'])
        ->and($tour->reviews)->toHaveCount(3);
});

it('never serializes operator contacts', function () {
    $operator = Operator::factory()->create();

    expect($operator->toArray())->not->toHaveKeys(['phone', 'whatsapp', 'email', 'contact_name', 'notes'])
        ->and($operator->phone)->not->toBeNull();
});

it('uses the tour commission when set, otherwise the operator commission', function () {
    $operator = Operator::factory()->create(['commission_rate' => 15]);

    expect(Tour::factory()->for($operator)->create()->effectiveCommissionRate())->toBe(15.0)
        ->and(Tour::factory()->for($operator)->create(['commission_rate' => 12])->effectiveCommissionRate())->toBe(12.0);
});

it('takes the lowest price from bookable departures and private prices', function () {
    $tour = Tour::factory()->create();
    Departure::factory()->for($tour)->create(['price_cents' => 33000]);
    Departure::factory()->for($tour)->create(['price_cents' => 20000, 'status' => DepartureStatus::Full]);
    Departure::factory()->for($tour)->create(['price_cents' => 15000, 'status' => DepartureStatus::Cancelled]);
    Departure::factory()->for($tour)->create(['price_cents' => 10000, 'starts_on' => now()->subMonth(), 'ends_on' => now()->subMonth()]);

    expect($tour->priceFromCents())->toBe(33000);

    PrivatePrice::factory()->for($tour)->create(['price_per_person_cents' => 29000]);

    expect($tour->priceFromCents())->toBe(29000);
});

it('counts seats left and bookability of a departure', function () {
    $departure = Departure::factory()->make(['seats_total' => 8, 'seats_booked' => 6]);

    expect($departure->seatsLeft())->toBe(2)
        ->and($departure->isBookable(2))->toBeTrue()
        ->and($departure->isBookable(3))->toBeFalse();

    $departure->status = DepartureStatus::Cancelled;
    expect($departure->isBookable(1))->toBeFalse();
});

it('orders tour regions by pivot sort', function () {
    $tour = Tour::factory()->create();
    [$a, $b] = Region::factory()->count(2)->create();
    $tour->regions()->attach([$a->id => ['sort' => 1], $b->id => ['sort' => 0]]);

    expect($tour->regions()->pluck('regions.id')->all())->toBe([$b->id, $a->id]);
});

it('casts tour item kinds to enums', function () {
    $tour = Tour::factory()->create();
    $tour->items()->create(['kind' => TourItemKind::Excluded, 'text' => 'Travel insurance']);

    expect($tour->excludedItems->first()->kind)->toBe(TourItemKind::Excluded);
});
