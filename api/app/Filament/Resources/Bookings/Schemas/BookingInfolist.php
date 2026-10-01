<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Resources\Tours\TourResource;
use App\Models\Booking;
use App\Services\Booking\OperatorMessages;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Booking card in the admin, as in the sketch of the launch document (section 06). */
class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (?int $cents) => $cents === null ? '—' : '$'.number_format($cents / 100, $cents % 100 ? 2 : 0);

        return $schema->columns(3)->components([
            Section::make('Турист')->columnSpan(1)->schema([
                TextEntry::make('customer_name')->label('Имя'),
                TextEntry::make('email')->label('Email')->copyable(),
                TextEntry::make('whatsapp')->label('WhatsApp')->copyable()->placeholder('—'),
                TextEntry::make('country')->label('Страна')->placeholder('—'),
                TextEntry::make('travelers.name')->label('Участники')->listWithLineBreaks()->placeholder('—'),
                TextEntry::make('special_requests')->label('Пожелания')->placeholder('—'),
            ]),
            Section::make('Тур')->columnSpan(1)->schema([
                TextEntry::make('tour.title')->label('Тур')
                    ->url(fn (Booking $r) => TourResource::getUrl('edit', ['record' => $r->tour_id])),
                TextEntry::make('operator.name')->label('Турфирма')
                    ->helperText(fn (Booking $record) => $record->operator->whatsapp ? "WhatsApp {$record->operator->whatsapp}" : null),
                TextEntry::make('date_from')->label('Даты')
                    ->formatStateUsing(fn (Booking $r) => $r->date_from->format('d.m.Y').' – '.$r->date_to->format('d.m.Y')),
                TextEntry::make('pricing_source')->label('Вариант')
                    ->formatStateUsing(fn (Booking $r) => $r->pricing_source->label()),
                TextEntry::make('adults')->label('Людей')
                    ->formatStateUsing(fn (Booking $r) => "{$r->adults} взр.".($r->children ? " + {$r->children} дет." : '')),
            ]),
            Section::make('Деньги')->columnSpan(1)->schema([
                TextEntry::make('total_cents')->label('Сумма тура')->formatStateUsing(fn ($state) => $money($state)),
                TextEntry::make('commission_rate')->label('Комиссия')->suffix('%'),
                TextEntry::make('deposit_cents')->label('Предоплата (наш доход)')->formatStateUsing(fn ($state) => $money($state))
                    ->color('success')->weight('bold'),
                TextEntry::make('balance_cents')->label('Остаток фирме на месте')->formatStateUsing(fn ($state) => $money($state)),
                TextEntry::make('payment_link_url')->label('Ссылка на оплату')->url(fn ($state) => $state)->openUrlInNewTab()->placeholder('—')->limit(40),
                TextEntry::make('payment_link_expires_at')->label('Ссылка действует до')->dateTime('d.m.Y H:i')->timezone(config('brand.timezone'))->placeholder('—'),
                TextEntry::make('paid_at')->label('Оплачено')->dateTime('d.m.Y H:i')->timezone(config('brand.timezone'))->placeholder('—'),
            ]),
            Section::make('Сообщение фирме')->columnSpan(2)->collapsible()
                ->description('Текст запроса мест. Кнопка «Написать фирме» вверху открывает WhatsApp с этим текстом.')
                ->schema([
                    TextEntry::make('operator_message')->hiddenLabel()->copyable()
                        ->state(fn (Booking $r) => $r->paid_at ? OperatorMessages::bookingSheet($r) : OperatorMessages::availabilityRequest($r))
                        ->extraAttributes(['style' => 'white-space: pre-line']),
                ]),
            Section::make('История')->columnSpan(1)->schema([
                RepeatableEntry::make('events')->hiddenLabel()->contained(false)->schema([
                    Grid::make(1)->schema([
                        TextEntry::make('to_status')->hiddenLabel()->badge(),
                        TextEntry::make('created_at')->hiddenLabel()->dateTime('d.m.Y H:i')->timezone(config('brand.timezone'))
                            ->color('gray')
                            ->suffix(fn ($record) => $record->user ? ' · '.$record->user->name : ' · система'),
                        TextEntry::make('note')->hiddenLabel()->color('gray')->visible(fn ($state) => filled($state)),
                    ]),
                ]),
            ]),
        ]);
    }
}
