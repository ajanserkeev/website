<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('operator')->withCount('bookings'))
            ->columns([
                ImageColumn::make('avatar_url')->label('')->circular()->imageSize(32),
                TextColumn::make('name')->label('Имя')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('role')->label('Роль')->badge()
                    ->description(fn (User $r) => $r->operator?->name),
                TextColumn::make('bookings_count')->label('Броней')
                    ->visible(fn ($livewire) => ($livewire->tableFilters['role']['value'] ?? null) === UserRole::Tourist->value),
                IconColumn::make('google_id')->label('Google')->boolean()->state(fn (User $r) => filled($r->google_id)),
                IconColumn::make('telegram_chat_id')->label('Telegram')->boolean()
                    ->state(fn (User $r) => filled($r->telegram_chat_id)),
                TextColumn::make('last_login_at')->label('Последний вход')->since()->placeholder('—'),
                TextColumn::make('created_at')->label('Создан')->date('d.m.Y'),
            ])
            ->filters([
                SelectFilter::make('role')->label('Роль')->options(UserRole::class),
            ])
            ->recordActions([EditAction::make()]);
    }
}
