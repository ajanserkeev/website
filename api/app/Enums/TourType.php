<?php

namespace App\Enums;

/** Kind of product in the catalog. */
enum TourType: string
{
    case MultiDay = 'multi_day';
    case DayTrip = 'day_trip';
    case Activity = 'activity';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::MultiDay => 'Multi-day tour',
            self::DayTrip => 'Day trip',
            self::Activity => 'Activity or expedition',
            self::Service => 'Transfer, guide or accommodation',
        };
    }
}
