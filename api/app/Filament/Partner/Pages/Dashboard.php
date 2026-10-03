<?php

namespace App\Filament\Partner\Pages;

use App\Filament\Partner\Widgets\PartnerOverview;
use App\Filament\Partner\Widgets\SalesByMonth;
use App\Filament\Partner\Widgets\ToursSummary;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Totals of the partner's sold bookings for a period: travelers, sales, platform commission, their share. */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Итоги';

    public function getSubheading(): ?string
    {
        return auth()->user()->operator?->name;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(3)->schema([
                Select::make('date_by')->label('Период считать по')->default('trip')->selectablePlaceholder(false)->options([
                    'trip' => 'дате начала тура',
                    'booked' => 'дате брони',
                ]),
                DatePicker::make('from')->label('С'),
                DatePicker::make('to')->label('По'),
            ]),
        ]);
    }

    public function getWidgets(): array
    {
        return [PartnerOverview::class, SalesByMonth::class, ToursSummary::class];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
