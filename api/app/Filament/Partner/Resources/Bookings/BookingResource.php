<?php

namespace App\Filament\Partner\Resources\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Partner\Resources\Bookings\Pages\ListBookings;
use App\Filament\Partner\Resources\Bookings\Pages\ViewBooking;
use App\Models\Booking;
use App\Services\Partner\PartnerStats;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** The partner's bookings, read-only: the platform team confirms them in /admin. */
class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'бронь';

    protected static ?string $pluralModelLabel = 'Брони';

    protected static ?string $recordTitleAttribute = 'code';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('operator_id', auth()->user()->operator_id ?? -1);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return self::bookingsTable($table);
    }

    /** Also used for the bookings of one tour on its page. */
    public static function bookingsTable(Table $table, bool $withTour = true): Table
    {
        $money = fn (?int $cents) => $cents === null ? null : '$'.number_format($cents / 100);

        return $table
            ->defaultSort('date_from', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('tour'))
            ->columns([
                TextColumn::make('code')->label('Код')->searchable()->weight('bold')->fontFamily('mono'),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('tour.title')->label('Тур')->limit(40)->visible($withTour),
                TextColumn::make('date_from')->label('Даты')->sortable()
                    ->formatStateUsing(fn (Booking $r) => $r->date_from->format('d.m').'–'.$r->date_to->format('d.m.Y')),
                TextColumn::make('adults')->label('Туристов')
                    ->formatStateUsing(fn (Booking $r) => $r->adults.($r->children ? " + {$r->children} дет." : '')),
                TextColumn::make('customer_name')->label('Турист')->searchable()->description(fn (Booking $r) => $r->country),
                TextColumn::make('total_cents')->label('Сумма тура')->formatStateUsing(fn ($state) => $money($state))->sortable(),
                TextColumn::make('deposit_cents')->label('Комиссия')->formatStateUsing(fn ($state) => $money($state))
                    ->description(fn (Booking $r) => (float) $r->commission_rate.'%')->color('warning'),
                TextColumn::make('balance_cents')->label('Вам')->formatStateUsing(fn ($state) => $money($state))->color('success')->weight('bold'),
                TextColumn::make('created_at')->label('Забронировано')->date('d.m.Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(BookingStatus::class)->multiple(),
                SelectFilter::make('state')->label('Показать')->default('sold')->options([
                    'sold' => 'С предоплатой',
                    'waiting' => 'Ждут подтверждения или оплаты',
                    'cancelled' => 'Отменённые',
                ])->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                    'sold' => $query->whereIn('status', PartnerStats::SOLD),
                    'waiting' => $query->whereIn('status', PartnerStats::WAITING),
                    'cancelled' => $query->whereIn('status', PartnerStats::CANCELLED),
                    default => $query,
                }),
            ])
            ->recordActions([ViewAction::make()->url(fn (Booking $r) => self::getUrl('view', ['record' => $r]))]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $money = fn (?int $cents) => $cents === null ? '—' : '$'.number_format($cents / 100);
        $paid = fn (Booking $r) => in_array($r->status, PartnerStats::SOLD, true);

        return $schema->columns(1)->components([
            Section::make()->schema([
                Grid::make(4)->schema([
                    TextEntry::make('status')->label('Статус')->badge(),
                    TextEntry::make('tour.title')->label('Тур'),
                    TextEntry::make('date_from')->label('Даты')
                        ->formatStateUsing(fn (Booking $r) => $r->date_from->format('d.m.Y').' – '.$r->date_to->format('d.m.Y')),
                    TextEntry::make('adults')->label('Туристов')
                        ->formatStateUsing(fn (Booking $r) => $r->adults.' взр.'.($r->children ? ", {$r->children} дет." : '')),
                    TextEntry::make('total_cents')->label('Сумма тура')->formatStateUsing(fn ($state) => $money($state)),
                    TextEntry::make('deposit_cents')->label('Комиссия площадки')
                        ->formatStateUsing(fn ($state, Booking $r) => $money($state).' ('.(float) $r->commission_rate.'%)'),
                    TextEntry::make('balance_cents')->label('Вам, турист платит на месте')->formatStateUsing(fn ($state) => $money($state))
                        ->weight('bold')->color('success'),
                    TextEntry::make('paid_at')->label('Предоплата получена')->dateTime('d.m.Y H:i')->placeholder('ещё нет'),
                ]),
            ]),
            Section::make('Туристы')->schema([
                Grid::make(4)->schema([
                    TextEntry::make('customer_name')->label('Контактное лицо'),
                    TextEntry::make('country')->label('Страна')->placeholder('—'),
                    // Contacts only for sold bookings: until the deposit is paid the platform talks to the traveler.
                    TextEntry::make('email')->label('Email')->visible($paid)->copyable(),
                    TextEntry::make('whatsapp')->label('WhatsApp')->visible($paid)->placeholder('—')->copyable(),
                    TextEntry::make('contacts_hidden')->label('Контакты')->hidden($paid)
                        ->state('Появятся после предоплаты'),
                ]),
                RepeatableEntry::make('travelers')->label('Участники')->schema([TextEntry::make('name')->hiddenLabel()])->grid(4)
                    ->placeholder('Не указаны'),
                TextEntry::make('special_requests')->label('Пожелания')->placeholder('—'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'view' => ViewBooking::route('/{record}'),
        ];
    }
}
