<?php

namespace App\Enums;

/** Line in the included / not included lists. */
enum TourItemKind: string
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
}
