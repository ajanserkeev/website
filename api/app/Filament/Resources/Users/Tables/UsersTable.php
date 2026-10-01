<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Имя')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('role')->label('Роль')->badge(),
                IconColumn::make('telegram_chat_id')->label('Telegram')->boolean()
                    ->state(fn ($record) => filled($record->telegram_chat_id)),
                TextColumn::make('created_at')->label('Создан')->date('d.m.Y'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
