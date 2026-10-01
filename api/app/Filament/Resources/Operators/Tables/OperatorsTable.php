<?php

namespace App\Filament\Resources\Operators\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OperatorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Фирма')->searchable()->sortable()
                    ->description(fn ($record) => $record->base_city),
                TextColumn::make('commission_rate')->label('Комиссия')->suffix('%')->sortable(),
                TextColumn::make('tours_count')->label('Туров')->counts('tours')->sortable(),
                TextColumn::make('tripadvisor_rating')->label('TripAdvisor')
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} ({$record->tripadvisor_reviews})" : null),
                TextColumn::make('google_rating')->label('Google')
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} ({$record->google_reviews})" : null),
                TextColumn::make('contract_signed_at')->label('Договор')->date('d.m.Y')->placeholder('нет')->sortable(),
                IconColumn::make('is_active')->label('Активна')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Активна'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
