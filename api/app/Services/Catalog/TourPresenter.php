<?php

namespace App\Services\Catalog;

use App\Enums\DepartureStatus;
use App\Enums\TourType;
use App\Models\Departure;
use App\Models\Tour;
use App\Services\Booking\DepositCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Values the site shows for a tour, computed from relations loaded by TourCatalog::eagerLoads()
 * (no extra queries per tour): price from, deposit, badges, rating.
 */
final class TourPresenter
{
    public function __construct(private readonly DepositCalculator $deposits) {}

    /** Future, not cancelled, in date order. Full departures stay visible but cannot be requested. */
    public function upcomingDepartures(Tour $tour): Collection
    {
        $today = Carbon::now(config('brand.timezone'))->toDateString();

        return $tour->departures
            ->filter(fn (Departure $d) => $d->starts_on->toDateString() > $today && $d->status !== DepartureStatus::Cancelled)
            ->sortBy('starts_on')
            ->values();
    }

    public function priceFromCents(Tour $tour): ?int
    {
        $prices = $this->upcomingDepartures($tour)
            ->filter(fn (Departure $d) => $d->status !== DepartureStatus::Full && $d->seatsLeft() > 0)
            ->pluck('price_cents')
            ->merge($tour->privatePrices->pluck('price_per_person_cents'));

        return $prices->isEmpty() ? null : (int) $prices->min();
    }

    public function depositFromCents(Tour $tour): ?int
    {
        $price = $this->priceFromCents($tour);

        return $price === null ? null : $this->deposits->depositForTour($tour, $price);
    }

    /**
     * Badges come only from real departure data (launch document, item 07).
     *
     * @return list<array{kind: string, label: string}>
     */
    public function badges(Tour $tour): array
    {
        $bookable = $this->upcomingDepartures($tour)->filter(fn (Departure $d) => $d->isBookable());
        $badges = [];

        if ($bookable->contains(fn (Departure $d) => $d->status === DepartureStatus::Guaranteed)) {
            $badges[] = ['kind' => 'guaranteed', 'label' => 'Guaranteed departure'];
        }
        $scarce = $bookable->map(fn (Departure $d) => $d->seatsLeft())->filter(fn (int $n) => $n <= 3);
        if ($scarce->isNotEmpty()) {
            $n = $scarce->min();
            $badges[] = ['kind' => 'spots', 'label' => "Only {$n} ".($n === 1 ? 'spot' : 'spots').' left'];
        }
        if ($tour->type === TourType::DayTrip) {
            $badges[] = ['kind' => 'day-trip', 'label' => 'Day trip'];
        }
        if ($tour->group_size_max <= 8 && $bookable->isNotEmpty()) {
            $badges[] = ['kind' => 'small-group', 'label' => 'Small group'];
        }
        if ($tour->departures->isEmpty() && $tour->privatePrices->isNotEmpty()) {
            $badges[] = ['kind' => 'private', 'label' => 'Private, any date'];
        }

        return $badges;
    }

    /**
     * Own reviews once there are at least 3; otherwise the operator's external rating with its source.
     *
     * @return array{value: float, count: int, source: string}|null
     */
    public function rating(Tour $tour): ?array
    {
        $reviews = $tour->reviews;
        if ($reviews->count() >= 3) {
            return [
                'value' => round($reviews->avg('rating'), 1),
                'count' => $reviews->count(),
                'source' => 'verified reviews',
            ];
        }

        $operator = $tour->operator;
        $external = collect([
            ['source' => 'TripAdvisor', 'rating' => $operator->tripadvisor_rating, 'reviews' => $operator->tripadvisor_reviews],
            ['source' => 'Google', 'rating' => $operator->google_rating, 'reviews' => $operator->google_reviews],
        ])->filter(fn ($r) => $r['rating'] !== null)->sortByDesc('reviews')->first();

        return $external ? [
            'value' => (float) $external['rating'],
            'count' => (int) $external['reviews'],
            'source' => 'on '.$external['source'],
        ] : null;
    }

    public function difficultyLevel(Tour $tour): string
    {
        return match (true) {
            $tour->difficulty <= 2 => 'easy',
            $tour->difficulty === 3 => 'moderate',
            default => 'challenging',
        };
    }
}
