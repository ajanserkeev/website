<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        $money = fn (?int $cents) => $cents === null ? null : '$'.number_format($cents / 100);

        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['tour', 'operator']))
            ->columns([
                TextColumn::make('code')->label('Код')->searchable()->weight('bold')->fontFamily('mono'),
                TextColumn::make('status')->label('Статус')->badge()->sortable(),
                TextColumn::make('tour.title')->label('Тур')->limit(40)->description(fn (Booking $r) => $r->operator->name),
                TextColumn::make('date_from')->label('Даты')->sortable()
                    ->formatStateUsing(fn (Booking $r) => $r->date_from->format('d.m').'–'.$r->date_to->format('d.m.Y')),
                TextColumn::make('adults')->label('Людей')
                    ->formatStateUsing(fn (Booking $r) => $r->adults.($r->children ? " + {$r->children} дет." : '')),
                TextColumn::make('customer_name')->label('Турист')->searchable(['customer_name', 'email'])
                    ->description(fn (Booking $r) => $r->country),
                TextColumn::make('total_cents')->label('Сумма')->formatStateUsing(fn ($state) => $money($state))
                    ->description(fn (Booking $r) => 'предоплата '.$money($r->deposit_cents)),
                TextColumn::make('created_at')->label('Создана')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(BookingStatus::class)->multiple(),
                Filter::make('needs_action')->label('Требуют действия')->default()
                    ->query(fn (Builder $query) => $query->whereIn('status', [BookingStatus::New, BookingStatus::Checking, BookingStatus::AwaitingPayment])),
            ])
            ->recordActions([ViewAction::make()]);
    }
}
