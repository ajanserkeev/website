<?php

namespace App\Filament\Resources\Collections\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->label('Подборка')->searchable(),
                TextColumn::make('tours_count')->label('Туров')->counts('tours'),
                IconColumn::make('is_featured')->label('На главной')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
