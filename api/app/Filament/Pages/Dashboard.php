<?php

namespace App\Filament\Pages;

use App\Filament\Partner\Pages\Dashboard as PartnerDashboard;
use App\Filament\Widgets\OperatorsSummary;
use App\Filament\Widgets\PlatformOverview;
use App\Filament\Widgets\PlatformSalesByMonth;
use Filament\Widgets\AccountWidget;

/** Super admin's start page: the same sales numbers as the partners see, for the whole platform. */
class Dashboard extends PartnerDashboard
{
    protected static ?string $title = 'Итоги площадки';

    public function getSubheading(): ?string
    {
        return 'Брони с оплаченной предоплатой по всем турфирмам';
    }

    public function getWidgets(): array
    {
        return [AccountWidget::class, PlatformOverview::class, PlatformSalesByMonth::class, OperatorsSummary::class];
    }
}
