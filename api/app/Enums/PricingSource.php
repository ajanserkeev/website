<?php

namespace App\Enums;

/** Where a booking took its price from; fixed at request time. */
enum PricingSource: string
{
    case Departure = 'departure';
    case Private = 'private';
    case Offer = 'offer';

    public function label(): string
    {
        return match ($this) {
            self::Departure => 'Group departure',
            self::Private => 'Private tour',
            self::Offer => 'Tailor-made offer',
        };
    }
}
