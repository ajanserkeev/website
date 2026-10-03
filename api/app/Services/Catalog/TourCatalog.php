<?php

namespace App\Services\Catalog;

use App\Enums\DepartureStatus;
use App\Models\Departure;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Public catalog: published tours of active operators, filtered and sorted like the site's filters.
 * Filtering runs in PHP over the loaded catalog: a few hundred tours at most until Meilisearch (plan, section 07).
 */
final class TourCatalog
{
    public const DURATIONS = ['1', '2-4', '5-8', '9+'];

    public const DIFFICULTIES = ['easy', 'moderate', 'challenging'];

    public const SORTS = ['recommended', 'price-asc', 'price-desc', 'duration'];

    public function __construct(private readonly TourPresenter $presenter) {}

    public function query(): Builder
    {
        return Tour::query()
            ->published()
            ->whereHas('operator', fn (Builder $q) => $q->where('is_active', true))
            ->with(self::eagerLoads());
    }

    /** Relations every public tour response needs. */
    public static function eagerLoads(): array
    {
        return [
            'operator',
            'regions',
            'activities',
            'departures',
            'privatePrices',
            'reviews' => fn ($q) => $q->published()->latest('published_at'),
            'media',
        ];
    }

    public function find(string $slug): ?Tour
    {
        return $this->query()->with(['days.place', 'items', 'faqs', 'operator.guides'])->where('slug', $slug)->first();
    }

    /**
     * @param  array{activity?: ?string, region?: ?string, month?: ?string, duration?: ?string, difficulty?: ?string, maxPrice?: ?int, sort?: ?string, slugs?: ?array}  $filters
     * @return Collection<int, Tour>
     */
    public function search(array $filters): Collection
    {
        $tours = $this->query()
            ->when($filters['slugs'] ?? null, fn (Builder $q, array $slugs) => $q->whereIn('slug', $slugs))
            ->get()
            ->filter(fn (Tour $tour) => $this->matches($tour, $filters));

        return $this->sort($tours, $filters['sort'] ?? 'recommended')->values();
    }

    private function matches(Tour $tour, array $f): bool
    {
        if (! empty($f['activity']) && ! $tour->activities->contains('slug', $f['activity'])) {
            return false;
        }
        if (! empty($f['region']) && ! $tour->regions->contains('slug', $f['region'])) {
            return false;
        }
        if (! empty($f['duration']) && ! $this->matchesDuration($tour->duration_days, $f['duration'])) {
            return false;
        }
        if (! empty($f['difficulty']) && $this->presenter->difficultyLevel($tour) !== $f['difficulty']) {
            return false;
        }
        if (! empty($f['maxPrice'])) {
            $price = $this->presenter->priceFromCents($tour);
            if ($price === null || $price > $f['maxPrice'] * 100) {
                return false;
            }
        }
        if (! empty($f['month']) && ! $this->matchesMonth($tour, $f['month'])) {
            return false;
        }

        return true;
    }

    private function matchesDuration(int $days, string $bucket): bool
    {
        return match ($bucket) {
            '1' => $days === 1,
            '2-4' => $days >= 2 && $days <= 4,
            '5-8' => $days >= 5 && $days <= 8,
            '9+' => $days >= 9,
            default => true,
        };
    }

    /** A group departure that month, or a private option with the month inside the season (plan, recommendation 16). */
    private function matchesMonth(Tour $tour, string $month): bool
    {
        $hasDeparture = $this->presenter->upcomingDepartures($tour)->contains(
            fn (Departure $d) => $d->starts_on->format('Y-m') === $month && $d->status !== DepartureStatus::Full
        );
        if ($hasDeparture) {
            return true;
        }

        $m = (int) substr($month, 5, 2);
        $from = $tour->season_from;
        $to = $tour->season_to;
        $inSeason = $from <= $to ? $m >= $from && $m <= $to : $m >= $from || $m <= $to;

        return $tour->privatePrices->isNotEmpty() && $inSeason;
    }

    private function sort(Collection $tours, string $sort): Collection
    {
        $price = fn (Tour $t) => $this->presenter->priceFromCents($t) ?? PHP_INT_MAX;

        return match ($sort) {
            'price-asc' => $tours->sortBy($price),
            'price-desc' => $tours->sortByDesc(fn (Tour $t) => $this->presenter->priceFromCents($t) ?? 0),
            'duration' => $tours->sortBy('duration_days'),
            default => $tours->sortByDesc('sort_weight'),
        };
    }
}
