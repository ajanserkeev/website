<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** A group departure date. 'guaranteed' runs regardless of group size. */
enum DepartureStatus: string implements HasColor, HasLabel
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

    /** Russian label for the admin panel. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Набор открыт',
            self::Guaranteed => 'Гарантирован',
            self::Full => 'Мест нет',
            self::Cancelled => 'Отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::Guaranteed => 'success',
            self::Full => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
