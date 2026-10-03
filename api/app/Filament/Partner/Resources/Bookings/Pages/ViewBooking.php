<?php

namespace App\Filament\Partner\Resources\Bookings\Pages;

use App\Filament\Partner\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return 'Бронь '.$this->getRecord()->code;
    }
}
