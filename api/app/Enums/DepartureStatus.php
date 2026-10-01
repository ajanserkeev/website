<?php

namespace App\Enums;

/** A group departure date. 'guaranteed' runs regardless of group size. */
enum DepartureStatus: string
{
    case Open = 'open';
    case Guaranteed = 'guaranteed';
    case Full = 'full';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Guaranteed => 'Guaranteed',
            self::Full => 'Full',
            self::Cancelled => 'Cancelled',
        };
    }
}
