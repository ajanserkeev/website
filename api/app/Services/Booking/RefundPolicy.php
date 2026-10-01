<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Deposit refunds (launch document, section 02): 100% at 30+ days before the start, 50% at 14–29 days,
 * nothing later; 100% when the operator cancels. Days are counted in Bishkek time (plan, recommendation 10).
 */
final class RefundPolicy
{
    public function daysBeforeStart(Booking $booking, ?CarbonInterface $at = null): int
    {
        $tz = config('brand.timezone');
        $today = CarbonImmutable::instance($at ?? now())->setTimezone($tz)->startOfDay();
        $start = CarbonImmutable::parse($booking->date_from->toDateString(), $tz)->startOfDay();

        return (int) $today->diffInDays($start, false);
    }

    /** Refund if the traveler cancels now. Zero when nothing has been paid yet. */
    public function touristCancellationCents(Booking $booking, ?CarbonInterface $at = null): int
    {
        if (! $this->depositPaid($booking)) {
            return 0;
        }
        $days = $this->daysBeforeStart($booking, $at);

        return match (true) {
            $days >= 30 => $booking->deposit_cents,
            $days >= 14 => intdiv($booking->deposit_cents, 2),
            default => 0,
        };
    }

    public function operatorCancellationCents(Booking $booking): int
    {
        return $this->depositPaid($booking) ? $booking->deposit_cents : 0;
    }

    private function depositPaid(Booking $booking): bool
    {
        return in_array($booking->status, [BookingStatus::DepositPaid, BookingStatus::VoucherSent], true);
    }
}
