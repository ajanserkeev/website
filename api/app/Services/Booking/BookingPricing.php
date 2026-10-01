<?php

namespace App\Services\Booking;

use App\Enums\PricingSource;
use App\Models\Departure;
use App\Models\PrivatePrice;
use App\Models\Tour;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Prices a booking request from the database only: the browser sends a departure or a date and the
 * number of travelers, never an amount (launch document, section 07).
 */
final class BookingPricing
{
    public function __construct(private readonly DepositCalculator $deposits) {}

    /**
     * @return array{pricing_source: PricingSource, departure_id: ?int, date_from: string, date_to: string,
     *     unit_price_cents: int, child_unit_price_cents: ?int, total_cents: int, commission_rate: float,
     *     deposit_cents: int, balance_cents: int}
     */
    public function quote(Tour $tour, ?int $departureId, ?string $date, int $adults, int $children): array
    {
        $people = $adults + $children;

        if ($departureId !== null) {
            /** @var ?Departure $departure */
            $departure = $tour->departures()->whereKey($departureId)->first();
            if (! $departure || ! $departure->isBookable($people) || ! $departure->starts_on->isAfter($this->today())) {
                throw ValidationException::withMessages(['departure_id' => 'These dates are no longer available for this group size.']);
            }

            return $this->priced($tour, PricingSource::Departure, $departure->id,
                $departure->starts_on->toDateString(), $departure->ends_on->toDateString(),
                $departure->price_cents, $departure->child_price_cents, $adults, $children);
        }

        if ($date === null) {
            throw ValidationException::withMessages(['date_from' => 'Choose a departure or a start date.']);
        }
        $start = CarbonImmutable::parse($date, config('brand.timezone'))->startOfDay();
        if (! $start->isAfter($this->today())) {
            throw ValidationException::withMessages(['date_from' => 'Choose a date from tomorrow on.']);
        }
        /** @var ?PrivatePrice $bracket */
        $bracket = $tour->privatePrices->first(fn (PrivatePrice $p) => $p->covers($people));
        if (! $bracket) {
            throw ValidationException::withMessages(['adults' => "A private tour for {$people} travelers is not available. Ask us on WhatsApp."]);
        }

        return $this->priced($tour, PricingSource::Private, null,
            $start->toDateString(), $start->addDays($tour->duration_days - 1)->toDateString(),
            $bracket->price_per_person_cents, $bracket->child_price_cents, $adults, $children);
    }

    private function priced(Tour $tour, PricingSource $source, ?int $departureId, string $from, string $to,
        int $unit, ?int $childUnit, int $adults, int $children): array
    {
        $total = $adults * $unit + $children * ($childUnit ?? $unit);
        $rate = $this->deposits->rateFor($tour);
        $deposit = $this->deposits->depositCents($total, $rate);

        return [
            'pricing_source' => $source,
            'departure_id' => $departureId,
            'date_from' => $from,
            'date_to' => $to,
            'unit_price_cents' => $unit,
            'child_unit_price_cents' => $childUnit,
            'total_cents' => $total,
            'commission_rate' => $rate,
            'deposit_cents' => $deposit,
            'balance_cents' => $total - $deposit,
        ];
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('brand.timezone'))->startOfDay();
    }
}
