<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Operators\OperatorResource;
use App\Models\Operator;
use App\Services\Partner\PartnerStats;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

/** Sold bookings per operator for the dashboard period. */
class OperatorsSummary extends TableWidget
{
    use InteractsWithPageFilters, SeesWholePlatform;

    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $sold = PartnerStats::fromFilters(null, $this->pageFilters)->soldConstraint();
        $money = fn ($state) => '$'.number_format(((int) $state) / 100);

        return $table
            ->heading('По турфирмам')
            ->query(
                Operator::query()
                    ->withCount(['bookings as sold_count' => $sold])
                    ->withSum(['bookings as adults_sum' => $sold], 'adults')
                    ->withSum(['bookings as children_sum' => $sold], 'children')
                    ->withSum(['bookings as total_sum' => $sold], 'total_cents')
                    ->withSum(['bookings as commission_sum' => $sold], 'deposit_cents')
                    ->withSum(['bookings as operator_sum' => $sold], 'balance_cents')
            )
            ->defaultSort('commission_sum', 'desc')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Турфирма')->weight('bold')
                    ->description(fn (Operator $r) => (float) $r->commission_rate.'% комиссии'),
                TextColumn::make('sold_count')->label('Броней')->sortable(),
                TextColumn::make('adults_sum')->label('Туристов')
                    ->formatStateUsing(fn ($state, Operator $r) => (int) $state + (int) $r->children_sum),
                TextColumn::make('total_sum')->label('Сумма туров')->sortable()->formatStateUsing($money),
                TextColumn::make('commission_sum')->label('Комиссия площадки')->sortable()->formatStateUsing($money)->color('warning')->weight('bold'),
                TextColumn::make('operator_sum')->label('Турфирме')->sortable()->formatStateUsing($money)->color('success'),
            ])
            ->recordUrl(fn (Operator $r) => OperatorResource::getUrl('edit', ['record' => $r]));
    }
}
