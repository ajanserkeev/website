<?php

namespace App\Payments;

use App\Models\Booking;

final class ManualGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function checkoutUrl(Booking $booking): ?string
    {
        return $booking->payment_link_url;
    }

    public function confirmsManually(): bool
    {
        return true;
    }
}
