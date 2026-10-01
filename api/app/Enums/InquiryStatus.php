<?php

namespace App\Enums;

/** Processing state of an inquiry in the admin. */
enum InquiryStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case OfferSent = 'offer_sent';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::OfferSent => 'Offer sent',
            self::Closed => 'Closed',
        };
    }
}
