<?php

namespace App\Filament\Resources\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Bookings come only from the site; staff move them along with the actions on the booking page. */
class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'бронь';

    protected static ?string $pluralModelLabel = 'Брони';

    protected static ?string $recordTitleAttribute = 'code';

    /** New and waiting-for-operator requests: the morning queue (launch document, section 11). */
    public static function getNavigationBadge(): ?string
    {
        $count = Booking::query()->whereIn('status', [BookingStatus::New, BookingStatus::Checking])->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'view' => ViewBooking::route('/{record}'),
        ];
    }
}
