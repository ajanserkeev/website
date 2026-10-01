<?php

namespace App\Enums;

/** A question about a tour or a 'Plan my trip' request. */
enum InquiryType: string
{
    case Question = 'question';
    case TailorMade = 'tailor_made';

    public function label(): string
    {
        return match ($this) {
            self::Question => 'Question',
            self::TailorMade => 'Plan my trip',
        };
    }
}
