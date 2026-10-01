<?php

namespace App\Filament\Resources\Posts\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Гайд')->searchable()->wrap(),
                TextColumn::make('published_at')->label('Опубликован')->date('d.m.Y')->placeholder('черновик')->sortable(),
                TextColumn::make('tours_count')->label('Туров внутри')->counts('tours'),
                TextColumn::make('updated_at')->label('Изменён')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('published_at')->label('Опубликован')->nullable(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
