<?php

namespace App\Filament\Partner\Resources\Bookings\Pages;

use App\Filament\Partner\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;
}
