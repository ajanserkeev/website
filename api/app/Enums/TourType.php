<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Kind of product in the catalog. */
enum TourType: string implements HasLabel
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

    /** Russian label for the admin panel. */
    public function getLabel(): string
    {
        return match ($this) {
            self::MultiDay => 'Многодневный тур',
            self::DayTrip => 'Однодневная экскурсия',
            self::Activity => 'Активность / экспедиция',
            self::Service => 'Услуга (трансфер, гид, жильё)',
        };
    }
}
