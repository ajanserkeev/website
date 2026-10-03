<?php

namespace App\Filament\Partner\Resources\Tours\RelationManagers;

use App\Filament\Partner\Resources\Bookings\BookingResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

/** Bookings of the tour, the same read-only list as in "Брони". */
class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Брони тура';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return BookingResource::bookingsTable($table, withTour: false);
    }
}
