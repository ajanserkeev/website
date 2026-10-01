<?php

namespace App\Filament\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Hand-ordered list of tours attached to a collection or a guide (pivot column `sort`). */
abstract class OrderedToursRelationManager extends RelationManager
{
    protected static string $relationship = 'tours';

    protected static ?string $title = 'Туры';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort')
            ->defaultSort('sort')
            ->paginated(false)
            ->columns([
                TextColumn::make('title')->label('Тур'),
                TextColumn::make('operator.name')->label('Турфирма')->color('gray'),
                TextColumn::make('status')->label('Статус')->badge(),
            ])
            ->headerActions([
                AttachAction::make()->label('Добавить тур')->preloadRecordSelect()->recordSelectSearchColumns(['title']),
            ])
            ->recordActions([
                DetachAction::make()->label('Убрать'),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
            ]);
    }
}
