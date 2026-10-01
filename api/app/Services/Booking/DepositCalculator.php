<?php

namespace App\Services\Booking;

use App\Models\Tour;

/**
 * Deposit = the platform's commission, paid online; the rest goes to the operator on day 1.
 * Rounded to whole dollars ("Pay $50 to book", not $49.50); web/src/lib/money.ts uses the same rule.
 */
final class DepositCalculator
{
    public function rateFor(Tour $tour): float
    {
        return $tour->effectiveCommissionRate();
    }

    public function depositCents(int $totalCents, float $rate): int
    {
        return (int) round($totalCents * $rate / 100 / 100) * 100;
    }

    public function depositForTour(Tour $tour, int $totalCents): int
    {
        return $this->depositCents($totalCents, $this->rateFor($tour));
    }
}
