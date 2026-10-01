<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Line in the included / not included lists. */
enum TourItemKind: string implements HasLabel
{
    case Included = 'included';
    case Excluded = 'excluded';

    public function label(): string
    {
        return match ($this) {
            self::Included => 'Included',
            self::Excluded => 'Not included',
        };
    }

    /** Russian label for the admin panel. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Included => 'Включено',
            self::Excluded => 'Не включено',
        };
    }
}
