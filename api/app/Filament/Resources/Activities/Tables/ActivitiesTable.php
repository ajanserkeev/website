<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('hero')->label('')->collection('hero')->conversion('w480')->square(),
                TextColumn::make('name')->label('Активность')->searchable(),
                TextColumn::make('slug')->label('Адрес')->color('gray'),
                TextColumn::make('tours_count')->label('Туров')->counts('tours'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
