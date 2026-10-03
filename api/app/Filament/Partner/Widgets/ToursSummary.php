<?php

namespace App\Filament\Partner\Widgets;

use App\Filament\Partner\Resources\Tours\TourResource;
use App\Filament\Partner\Widgets\Concerns\ScopesSales;
use App\Models\Tour;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Sold bookings per tour for the period. */
class ToursSummary extends TableWidget
{
    use ScopesSales;

    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $sold = $this->stats()->soldConstraint();
        $money = fn ($state) => '$'.number_format(((int) $state) / 100);

        return $table
            ->heading('По турам')
            ->query(
                Tour::query()
                    ->when($this->operatorId(), fn (Builder $q, int $id) => $q->where('operator_id', $id))
                    ->withCount(['bookings as sold_count' => $sold])
                    ->withSum(['bookings as travelers_sum' => $sold], 'adults')
                    ->withSum(['bookings as children_sum' => $sold], 'children')
                    ->withSum(['bookings as total_sum' => $sold], 'total_cents')
                    ->withSum(['bookings as commission_sum' => $sold], 'deposit_cents')
                    ->withSum(['bookings as operator_sum' => $sold], 'balance_cents')
            )
            ->defaultSort('total_sum', 'desc')
            ->paginated(false)
            ->columns([
                TextColumn::make('title')->label('Тур')->weight('bold')->wrap()
                    ->description(fn (Tour $r) => $r->status->getLabel()),
                TextColumn::make('sold_count')->label('Броней')->sortable(),
                TextColumn::make('travelers_sum')->label('Туристов')->sortable()
                    ->formatStateUsing(fn ($state, Tour $r) => (int) $state + (int) $r->children_sum),
                TextColumn::make('total_sum')->label('Сумма туров')->sortable()->formatStateUsing($money),
                TextColumn::make('commission_sum')->label('Комиссия площадки')->sortable()->formatStateUsing($money)->color('warning'),
                TextColumn::make('operator_sum')->label($this->operatorId() === null ? 'Турфирме' : 'Вам')->sortable()
                    ->formatStateUsing($money)->color('success')->weight('bold'),
            ])
            ->recordUrl(fn (Tour $r) => $this->operatorId() === null ? null : TourResource::getUrl('view', ['record' => $r]));
    }
}
