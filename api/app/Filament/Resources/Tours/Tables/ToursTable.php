<?php

namespace App\Filament\Resources\Tours\Tables;

use App\Enums\TourStatus;
use App\Enums\TourType;
use App\Filament\Resources\Tours\TourResource;
use App\Models\Tour;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ToursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_weight', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['operator', 'regions']))
            ->columns([
                SpatieMediaLibraryImageColumn::make('gallery')->label('')->collection('gallery')->conversion('w480')->limit(1)->square(),
                TextColumn::make('title')->label('Тур')->searchable()->sortable()
                    ->description(fn (Tour $record) => $record->operator?->name),
                TextColumn::make('status')->label('Статус')->badge()->sortable(),
                TextColumn::make('type')->label('Тип')->badge()->color('gray')->toggleable(),
                TextColumn::make('duration_days')->label('Дней')->sortable(),
                TextColumn::make('regions.name')->label('Регионы')->badge()->color('gray')->toggleable(),
                TextColumn::make('price_from')->label('Цена от')
                    ->state(fn (Tour $record) => $record->priceFromCents())
                    ->formatStateUsing(fn (?int $state) => $state === null ? null : '$'.number_format($state / 100))
                    ->placeholder('нет цен'),
                TextColumn::make('departures_count')->label('Заездов')->counts('departures')->sortable()->toggleable(),
                TextColumn::make('sort_weight')->label('Вес')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Изменён')->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(TourStatus::class),
                SelectFilter::make('type')->label('Тип')->options(TourType::class),
                SelectFilter::make('operator')->label('Турфирма')->relationship('operator', 'name')->searchable()->preload(),
                SelectFilter::make('regions')->label('Регион')->relationship('regions', 'name')->multiple()->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view_on_site')->label('На сайте')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Tour $record) => TourResource::siteUrl($record), shouldOpenInNewTab: true),
            ]);
    }
}
