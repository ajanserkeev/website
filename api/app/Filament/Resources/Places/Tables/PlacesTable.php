<?php

namespace App\Filament\Resources\Places\Tables;

use App\Enums\PlaceKind;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')->label('')->collection('photo')->conversion('w480')->square(),
                TextColumn::make('name')->label('Место')->searchable(),
                TextColumn::make('kind')->label('Тип')->badge(),
                TextColumn::make('region.name')->label('Регион')->placeholder('—'),
                TextColumn::make('altitude_m')->label('Высота')->suffix(' м')->sortable(),
                TextColumn::make('days_count')->label('Дней в турах')->counts('days'),
                IconColumn::make('is_featured')->label('Крупно')->boolean(),
                IconColumn::make('is_published')->label('На сайте')->boolean(),
            ])
            ->filters([
                SelectFilter::make('kind')->label('Тип')->options(PlaceKind::class),
                SelectFilter::make('region')->label('Регион')->relationship('region', 'name'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
