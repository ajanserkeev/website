<?php

namespace App\Payments;

use App\Models\Booking;

/**
 * Acquirer integration (step 6.2). Until a provider is connected, ManualGateway is used: staff paste the
 * payment link from the acquirer's cabinet and mark the deposit as received (plan, recommendation 11).
 */
interface PaymentGateway
{
    public function name(): string;

    /** URL where the traveler pays the deposit. */
    public function checkoutUrl(Booking $booking): ?string;

    /** Whether staff confirm payments by hand in the admin (no webhook). */
    public function confirmsManually(): bool;
}
