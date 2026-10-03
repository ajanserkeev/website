<?php

namespace App\Filament\Partner\Widgets;

use App\Filament\Partner\Widgets\Concerns\ScopesSales;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PartnerOverview extends StatsOverviewWidget
{
    use ScopesSales;

    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $t = $this->stats()->totals();
        $rate = $t['average_commission_rate'] !== null ? " ({$t['average_commission_rate']}%)" : '';

        return [
            Stat::make('Брони с предоплатой', $t['bookings'])
                ->description("ждут оплаты: {$t['waiting']} · отменены: {$t['cancelled']}")
                ->icon(Heroicon::OutlinedTicket),
            Stat::make('Туристов', $t['travelers'])
                ->description('взрослые и дети в оплаченных бронях')
                ->icon(Heroicon::OutlinedUserGroup),
            Stat::make('Сумма туров', self::money($t['total_cents']))
                ->description('полная стоимость по оплаченным броням')
                ->icon(Heroicon::OutlinedBanknotes),
            Stat::make('Комиссия площадки'.$rate, self::money($t['commission_cents']))
                ->description('турист заплатил её как предоплату на сайте')
                ->color('warning')
                ->icon(Heroicon::OutlinedReceiptPercent),
            Stat::make($this->operatorId() === null ? 'Турфирмам' : 'Вам к получению', self::money($t['operator_cents']))
                ->description('турист платит на месте')
                ->color('success')
                ->icon(Heroicon::OutlinedWallet),
        ];
    }
}
