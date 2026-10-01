<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('name')->label('Имя')->required(),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                Select::make('role')->label('Роль')->options(UserRole::class)->default(UserRole::Manager)->required(),
                TextInput::make('telegram_chat_id')->label('Telegram chat id')
                    ->helperText('Сюда приходят уведомления о заявках и оплатах (шаг 4.8).'),
                TextInput::make('password')->label('Пароль')->password()->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->minLength(10)
                    ->helperText('При редактировании: оставьте пустым, чтобы не менять.'),
            ]),
        ]);
    }
}
